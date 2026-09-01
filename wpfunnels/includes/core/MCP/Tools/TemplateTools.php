<?php
/**
 * TemplateTools — MCP abilities for the WPFunnels Template Library.
 *
 * @package WPFunnels\MCP\Tools
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Helpers\MCPHelper;
use WPFunnels\Rest\Controllers\TemplateLibraryController;
use WPFunnels\TemplateLibrary\Manager as TemplateLibraryManager;
use WPFunnels\Wpfnl_functions;

/**
 * Class TemplateTools
 */
class TemplateTools {

	/**
	 * Funnel types the remote template API understands.
	 */
	private const TYPES = [ 'wc', 'lead', 'lms', 'store_checkout' ];

	/**
	 * Register tool definitions.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/list-funnel-templates' => [
				'description' => 'List real pre-built funnel templates from the WPFunnels template library — each one includes a ready-made page-builder design for every step, not just a step list. Filterable by funnel type, industry/category, page builder, and free-text search. Call this before building a funnel from scratch: prefer importing a relevant template and then configuring it over hand-assembling steps.',
				'category'    => 'template',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'type'     => [
							'type'        => 'string',
							'enum'        => self::TYPES,
							'description' => 'Funnel type to filter by. Defaults to wc.',
						],
						'category' => [
							'type'        => 'string',
							'description' => 'Industry/category slug or name to filter by (e.g. "fitness", "ebook", "saas"). Match this against the site\'s products or niche when possible.',
						],
						'builder'  => [
							'type'        => 'string',
							'description' => 'Filter by builder: gutenberg, elementor, divi, bricks, oxygen. Defaults to the site\'s active builder.',
						],
						'search'   => [
							'type'        => 'string',
							'description' => 'Free-text search term matched against the template name and industry (case-insensitive substring match, applied on top of any type/category/builder filters).',
						],
					],
				],
				'callback'    => [ __CLASS__, 'listTemplates' ],
			],

			'wpfunnels/get-funnel-template' => [
				'description' => 'Get full detail for one funnel template by its remote ID — its steps (id, type, title) and industry — before deciding whether to import it.',
				'category'    => 'template',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'template_id' => [
							'type'        => 'integer',
							'description' => 'Remote template ID from list-funnel-templates.',
						],
						'builder'     => [
							'type'        => 'string',
							'description' => 'Builder the template was fetched for. Defaults to the site\'s active builder.',
						],
					],
					'required'   => [ 'template_id' ],
				],
				'callback'    => [ __CLASS__, 'getTemplate' ],
			],

			'wpfunnels/import-funnel-template' => [
				'description' => 'Import a real funnel template — creates the funnel and every step with its actual page-builder design cloned from the template, not a blank shell. Use this instead of create-funnel + create-step whenever a matching template exists. After import, continue configuring it (assign-products-to-step, upsert-order-bump, set-offer-routing) exactly as you would a hand-built funnel, then propose publishing.',
				'category'    => 'template',
				'readonly'    => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'template_id' => [
							'type'        => 'integer',
							'description' => 'Remote template ID from list-funnel-templates or get-funnel-template.',
						],
						'name'        => [
							'type'        => 'string',
							'description' => 'Name for the imported funnel. Defaults to the template title.',
						],
						'type'        => [
							'type'        => 'string',
							'enum'        => self::TYPES,
							'description' => 'Funnel type to import. If omitted, the tool searches every type for a matching template ID.',
						],
						'status'      => [
							'type'        => 'string',
							'enum'        => [ 'publish', 'draft' ],
							'default'     => 'draft',
						],
						'builder'     => [
							'type'        => 'string',
							'description' => 'Builder the template was fetched for. Defaults to the site\'s active builder.',
						],
					],
					'required'   => [ 'template_id' ],
				],
				'callback'    => [ __CLASS__, 'importTemplate' ],
			],

			'wpfunnels/import-step-template' => [
				'description' => 'Import ONE step\'s real page-builder design from the template library onto an existing funnel — creates a single new step post cloned from the template step, not a whole funnel. Use this instead of import-funnel-template when you only want to add one more page (e.g. an extra upsell or a bonus landing page) from the library to a funnel you already built or already imported. The new step is appended to the funnel\'s step list; open the canvas afterward to position it and wire up routing (set-offer-routing).',
				'category'    => 'template',
				'readonly'    => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'funnel_id'   => [
							'type'        => 'integer',
							'description' => 'Existing funnel to import the step into. Use wpfunnels/list-funnels to find it.',
						],
						'template_id' => [
							'type'        => 'integer',
							'description' => 'Remote template ID that contains the step, from list-funnel-templates or get-funnel-template.',
						],
						'step_id'     => [
							'type'        => 'integer',
							'description' => 'Remote step ID within that template, from get-funnel-template\'s steps list.',
						],
						'type'        => [
							'type'        => 'string',
							'enum'        => self::TYPES,
							'description' => 'Funnel type the template belongs to. If omitted, the tool searches every type for a matching template ID.',
						],
						'builder'     => [
							'type'        => 'string',
							'description' => 'Builder the template was fetched for. Defaults to the site\'s active builder.',
						],
					],
					'required'   => [ 'funnel_id', 'template_id', 'step_id' ],
				],
				'callback'    => [ __CLASS__, 'importStepTemplate' ],
			],

			// wpfunnels/apply-template-to-step — TODO(ai-v2): not implemented.
			// Wpfnl_Source_Remote::import_step() can clone one remote step onto
			// an *existing* funnel, but wiring that safely needs a way to pick
			// which live step it replaces (and what happens to routing/meta
			// that pointed at the old step) — a follow-up beyond this pass.
		];
	}

	/**
	 * Resolve which builder to fetch templates for.
	 *
	 * @param array $input Tool input.
	 * @return string
	 */
	private static function builderType( $input ) {
		return isset( $input['builder'] ) && '' !== $input['builder']
			? sanitize_text_field( $input['builder'] )
			: Wpfnl_functions::get_builder_type();
	}

	/**
	 * Fetch the cached/remote template set for one funnel type.
	 *
	 * @param string $type    Funnel type.
	 * @param string $builder Builder slug.
	 * @return array
	 */
	private static function fetchTemplates( $type, $builder ) {
		$controller = new TemplateLibraryController();
		$data       = $controller->get_funnels_data( $type, false, [], false, $builder );
		$data       = $controller->prepare_custom_step( $data );

		return isset( $data['templates'] ) && is_array( $data['templates'] ) ? $data['templates'] : [];
	}

	/**
	 * Extract a template's remote ID regardless of casing.
	 *
	 * @param array $template Raw template record.
	 * @return int
	 */
	private static function templateId( $template ) {
		if ( isset( $template['ID'] ) ) {
			return (int) $template['ID'];
		}
		return isset( $template['id'] ) ? (int) $template['id'] : 0;
	}

	/**
	 * Whether a template is pro-only.
	 *
	 * @param array $template Raw template record.
	 * @return bool
	 */
	private static function isProTemplate( $template ) {
		if ( ! empty( $template['is_pro'] ) ) {
			return true;
		}
		return isset( $template['templateType'] ) && 'pro' === $template['templateType'];
	}

	/**
	 * A template's industry taxonomy term.
	 *
	 * The remote API puts this under `wpf_funnel_industry` as a full term
	 * object ({name, slug, ...}), not `industry`.
	 *
	 * @param array $template Raw template record.
	 * @return array{name: string, slug: string}
	 */
	private static function industryOf( $template ) {
		$industry = isset( $template['wpf_funnel_industry'] ) && is_array( $template['wpf_funnel_industry'] )
			? $template['wpf_funnel_industry']
			: [];

		return [
			'name' => isset( $industry['name'] ) ? (string) $industry['name'] : '',
			'slug' => isset( $industry['slug'] ) ? (string) $industry['slug'] : '',
		];
	}

	/**
	 * Summarize a template for list output.
	 *
	 * @param array $template Raw template record.
	 * @return array
	 */
	private static function summarizeTemplate( $template ) {
		$steps = isset( $template['steps'] ) && is_array( $template['steps'] ) ? $template['steps'] : [];

		return [
			'id'          => self::templateId( $template ),
			'name'        => isset( $template['title'] ) ? $template['title'] : '',
			'plan'        => self::isProTemplate( $template ) ? 'pro' : 'free',
			'industry'    => self::industryOf( $template )['name'],
			'steps_count' => count( $steps ),
			'steps'       => array_values(
				array_map(
					static function ( $step ) {
						return isset( $step['step_type'] ) ? $step['step_type'] : '';
					},
					$steps
				)
			),
			'preview_url' => isset( $template['link'] ) ? $template['link'] : '',
		];
	}

	/**
	 * Find one template by ID, optionally restricted to a single type.
	 *
	 * @param int         $template_id Remote template ID.
	 * @param string      $builder     Builder slug.
	 * @param string|null $type        Funnel type to search, or null to search every type.
	 * @return array{template: array|null, type: string}
	 */
	private static function findTemplate( $template_id, $builder, $type = null ) {
		$types_to_try = $type ? [ $type ] : self::TYPES;

		foreach ( $types_to_try as $candidate_type ) {
			foreach ( self::fetchTemplates( $candidate_type, $builder ) as $template ) {
				if ( self::templateId( $template ) === $template_id ) {
					return [ 'template' => $template, 'type' => $candidate_type ];
				}
			}
		}

		return [ 'template' => null, 'type' => '' ];
	}

	/**
	 * List templates.
	 *
	 * @param array $input Tool input.
	 * @return array
	 */
	public static function listTemplates( $input = [] ) {
		$type     = isset( $input['type'] ) ? sanitize_text_field( $input['type'] ) : 'wc';
		$builder  = self::builderType( $input );
		$category = isset( $input['category'] ) ? sanitize_text_field( $input['category'] ) : '';
		$search   = isset( $input['search'] ) ? sanitize_text_field( $input['search'] ) : '';

		$templates = self::fetchTemplates( $type, $builder );

		if ( '' !== $category ) {
			$wanted_slug = sanitize_title( $category );
			$templates   = array_values(
				array_filter(
					$templates,
					static function ( $template ) use ( $category, $wanted_slug ) {
						$industry = self::industryOf( $template );
						return $wanted_slug === $industry['slug'] || strtolower( $category ) === strtolower( $industry['name'] );
					}
				)
			);
		}

		// The underlying template API (TemplateLibraryController::get_funnels_data())
		// has no free-text search parameter of its own — it only accepts
		// type/step/builder — so `search` is applied client-side against the
		// name and industry of whatever the type/builder/category filters above
		// already returned.
		if ( '' !== $search ) {
			$templates = array_values(
				array_filter(
					$templates,
					static function ( $template ) use ( $search ) {
						$name     = isset( $template['title'] ) ? (string) $template['title'] : '';
						$industry = self::industryOf( $template )['name'];
						return false !== stripos( $name, $search ) || false !== stripos( $industry, $search );
					}
				)
			);
		}

		$summaries = array_map( [ __CLASS__, 'summarizeTemplate' ], $templates );

		return [
			'count'     => count( $summaries ),
			'templates' => $summaries,
		];
	}

	/**
	 * Get template detail.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getTemplate( $input = [] ) {
		$template_id = isset( $input['template_id'] ) ? (int) $input['template_id'] : 0;
		if ( ! $template_id ) {
			return MCPHelper::error( 'missing_template_id', 'template_id is required.' );
		}

		$builder = self::builderType( $input );
		$found   = self::findTemplate( $template_id, $builder );

		if ( null === $found['template'] ) {
			return MCPHelper::error( 'template_not_found', sprintf( 'No template found with ID %d.', $template_id ) );
		}

		$template = $found['template'];
		$steps    = isset( $template['steps'] ) && is_array( $template['steps'] ) ? $template['steps'] : [];

		return [
			'id'          => self::templateId( $template ),
			'name'        => isset( $template['title'] ) ? $template['title'] : '',
			'type'        => $found['type'],
			'plan'        => self::isProTemplate( $template ) ? 'pro' : 'free',
			'industry'    => self::industryOf( $template )['name'],
			'preview_url' => isset( $template['link'] ) ? $template['link'] : '',
			'steps'       => array_map(
				static function ( $step ) {
					return [
						'id'        => isset( $step['ID'] ) ? (int) $step['ID'] : ( isset( $step['id'] ) ? (int) $step['id'] : 0 ),
						'step_type' => isset( $step['step_type'] ) ? $step['step_type'] : '',
						'title'     => isset( $step['title'] ) ? $step['title'] : '',
					];
				},
				$steps
			),
		];
	}

	/**
	 * Import a template: creates the funnel, then clones every step's real
	 * page-builder design onto it by driving the same
	 * Wpfnl_Source_Remote::import_funnel() / import_step() calls the
	 * Template Library UI uses.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function importTemplate( $input = [] ) {
		$template_id = isset( $input['template_id'] ) ? (int) $input['template_id'] : 0;
		if ( ! $template_id ) {
			return MCPHelper::error( 'missing_template_id', 'template_id is required.' );
		}

		$builder      = self::builderType( $input );
		$explicit_type = isset( $input['type'] ) && in_array( $input['type'], self::TYPES, true ) ? $input['type'] : null;
		$found        = self::findTemplate( $template_id, $builder, $explicit_type );

		if ( null === $found['template'] ) {
			return MCPHelper::error( 'template_not_found', sprintf( 'No template found with ID %d.', $template_id ) );
		}

		$template      = $found['template'];
		$resolved_type = $found['type'];
		$steps         = isset( $template['steps'] ) && is_array( $template['steps'] ) ? $template['steps'] : [];

		if ( empty( $steps ) ) {
			return MCPHelper::error( 'template_has_no_steps', 'This template has no steps to import.' );
		}

		$name = isset( $input['name'] ) && '' !== trim( (string) $input['name'] )
			? sanitize_text_field( $input['name'] )
			: ( isset( $template['title'] ) && '' !== $template['title']
				? sanitize_text_field( $template['title'] )
				: sprintf( 'Imported Template %d', $template_id ) );

		$manager = new TemplateLibraryManager();
		$source  = $manager->get_source( 'remote' );
		if ( ! $source ) {
			return MCPHelper::error( 'template_source_unavailable', 'The remote template source is unavailable.' );
		}

		$status = isset( $input['status'] ) && 'publish' === $input['status'] ? 'publish' : 'draft';

		$import_args = [
			'source'   => 'remote',
			'remoteID' => $template_id,
			'name'     => $name,
			'type'     => 'store_checkout' === $resolved_type ? 'wc' : $resolved_type,
			'status'   => $status,
			'steps'    => $steps,
		];
		if ( 'store_checkout' === $resolved_type ) {
			$import_args['is_store_checkout'] = 'true';
		}

		$import_result = $source->import_funnel( $import_args );

		if ( empty( $import_result['success'] ) || empty( $import_result['funnelID'] ) ) {
			return MCPHelper::error(
				'import_failed',
				isset( $import_result['message'] ) ? $import_result['message'] : 'Could not import the template funnel.'
			);
		}

		$funnel_id      = (int) $import_result['funnelID'];
		$imported_steps = [];

		foreach ( $steps as $step ) {
			$step_result = $source->import_step(
				[
					'funnelID'   => $funnel_id,
					'step'       => $step,
					'source'     => 'remote',
					'importType' => 'templates',
				]
			);

			if ( empty( $step_result['success'] ) || empty( $step_result['stepID'] ) ) {
				continue;
			}

			$imported_steps[] = [
				'step_id'   => (int) $step_result['stepID'],
				'step_type' => isset( $step['step_type'] ) ? $step['step_type'] : '',
			];
		}

		if ( empty( $imported_steps ) ) {
			return MCPHelper::error( 'import_failed', 'The funnel was created but no steps could be imported.' );
		}

		ContextTools::invalidateCache();

		return [
			'success'        => true,
			'funnel_id'      => $funnel_id,
			'name'           => $name,
			'template_id'    => $template_id,
			'imported_steps' => $imported_steps,
			'canvas_url'     => MCPHelper::canvasUrl( $funnel_id ),
			'next_step'      => 'Design and step structure are imported. Continue with assign-products-to-step, upsert-order-bump, and set-offer-routing to finish configuring it, then propose publishing.',
		];
	}

	/**
	 * Import a single template step onto an existing funnel — the
	 * step-level counterpart to importTemplate(), driven by the same
	 * Wpfnl_Source_Remote::import_step() call the Template Library UI's
	 * "add step from library" flow uses.
	 *
	 * Unlike importTemplate()'s per-step loop — which runs against a
	 * funnel freshly cloned from the same template, so its canvas already
	 * carries placeholder nodes keyed by each remote step id — this
	 * targets a funnel that already exists and has no such placeholder.
	 * So this intentionally omits `importType => 'templates'` (which would
	 * try, and fail, to swap a placeholder step id in `_funnel_data`) and
	 * instead passes `isSingleStep => 'yes'`, which is what
	 * Wpfnl_Source_Remote::import_step() uses to append the new step to
	 * the funnel's `_steps_order` directly. The step is not auto-positioned
	 * on the visual canvas layout — see the apply-template-to-step TODO
	 * above for why that wiring (picking a position, rerouting) is out of
	 * scope here too.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function importStepTemplate( $input = [] ) {
		$funnel = MCPHelper::requireFunnel( isset( $input['funnel_id'] ) ? $input['funnel_id'] : 0 );
		if ( is_wp_error( $funnel ) ) {
			return $funnel;
		}

		$template_id = isset( $input['template_id'] ) ? (int) $input['template_id'] : 0;
		if ( ! $template_id ) {
			return MCPHelper::error( 'missing_template_id', 'template_id is required.' );
		}

		$step_id = isset( $input['step_id'] ) ? (int) $input['step_id'] : 0;
		if ( ! $step_id ) {
			return MCPHelper::error( 'missing_step_id', 'step_id is required.' );
		}

		$builder       = self::builderType( $input );
		$explicit_type = isset( $input['type'] ) && in_array( $input['type'], self::TYPES, true ) ? $input['type'] : null;
		$found         = self::findTemplate( $template_id, $builder, $explicit_type );

		if ( null === $found['template'] ) {
			return MCPHelper::error( 'template_not_found', sprintf( 'No template found with ID %d.', $template_id ) );
		}

		$template_steps = isset( $found['template']['steps'] ) && is_array( $found['template']['steps'] ) ? $found['template']['steps'] : [];
		$step           = null;

		foreach ( $template_steps as $candidate ) {
			if ( self::templateId( $candidate ) === $step_id ) {
				$step = $candidate;
				break;
			}
		}

		if ( null === $step ) {
			return MCPHelper::error(
				'template_step_not_found',
				sprintf( 'No step with ID %d was found in template %d.', $step_id, $template_id )
			);
		}

		$manager = new TemplateLibraryManager();
		$source  = $manager->get_source( 'remote' );
		if ( ! $source ) {
			return MCPHelper::error( 'template_source_unavailable', 'The remote template source is unavailable.' );
		}

		$step_result = $source->import_step(
			[
				'funnelID'     => $funnel->ID,
				'step'         => $step,
				'source'       => 'remote',
				'isSingleStep' => 'yes',
			]
		);

		if ( empty( $step_result['success'] ) || empty( $step_result['stepID'] ) ) {
			return MCPHelper::error(
				'import_failed',
				isset( $step_result['message'] ) ? $step_result['message'] : 'Could not import the template step.'
			);
		}

		ContextTools::invalidateCache();

		return [
			'success'     => true,
			'funnel_id'   => (int) $funnel->ID,
			'template_id' => $template_id,
			'step'        => [
				'step_id'   => (int) $step_result['stepID'],
				'step_type' => isset( $step['step_type'] ) ? $step['step_type'] : '',
				'title'     => isset( $step['title'] ) ? $step['title'] : '',
				'edit_url'  => isset( $step_result['stepEditLink'] ) ? $step_result['stepEditLink'] : '',
				'view_url'  => isset( $step_result['stepViewLink'] ) ? $step_result['stepViewLink'] : '',
			],
			'canvas_url'  => MCPHelper::canvasUrl( $funnel->ID ),
			'next_step'   => 'The step has been created and appended to the funnel\'s step order. Open the canvas to position it and wire up routing (set-offer-routing), then continue configuring it (assign-products-to-step, upsert-order-bump) as needed.',
		];
	}
}
