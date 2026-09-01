<?php
/**
 * PageContentTools — MCP abilities for WPFunnels Step Page composition and copy editing.
 *
 * @package WPFunnels\MCP\Tools
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\AI\AIInit;
use WPFunnels\AI\Settings\AISettings;
use WPFunnels\MCP\Helpers\MCPHelper;
use WPFunnels\MCP\Support\ElementorLayoutBuilder;

/**
 * Class PageContentTools
 */
class PageContentTools {

	/**
	 * Register tool definitions.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/compose-step-page' => [
				'description' => 'Build a complete layout and content structure for a step page in the active page builder.',
				'category'    => 'content',
				'readonly'    => false,
				'destructive' => true,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'step_id'     => [
							'type'        => 'integer',
							'description' => 'ID of the step.',
						],
						'headline'    => [
							'type'        => 'string',
							'description' => 'Primary page headline.',
						],
						'subheadline' => [
							'type'        => 'string',
							'description' => 'Supporting subheadline or value proposition.',
						],
						'cta_text'    => [
							'type'        => 'string',
							'description' => 'Call-to-action button text.',
						],
						'benefits'    => [
							'type'        => 'array',
							'description' => 'List of benefit bullet points.',
							'items'       => [ 'type' => 'string' ],
						],
					],
					'required'   => [ 'step_id', 'headline' ],
				],
				'callback'    => [ __CLASS__, 'composeStepPage' ],
			],

			'wpfunnels/compose-step-layout' => [
				'label'               => __( 'Compose Step Page Layout', 'wpfnl' ),
				'description'         => 'Build a real Elementor widget layout for a step page using WPFunnels\' own widgets (opt-in, checkout, offer, next-step), chosen from the step\'s type. Buttons automatically pick up the site\'s Elementor brand color when one is set — no need to pass a color. Also updates the same copy fields as compose-step-page.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id'            => [
							'type'        => 'integer',
							'description' => 'ID of the step.',
						],
						'headline'           => [
							'type'        => 'string',
							'description' => 'Primary page headline.',
						],
						'subheadline'        => [
							'type'        => 'string',
							'description' => 'Supporting subheadline or value proposition.',
						],
						'cta_text'           => [
							'type'        => 'string',
							'description' => 'Call-to-action button text.',
						],
						'benefits'           => [
							'type'        => 'array',
							'description' => 'List of benefit bullet points.',
							'items'       => [ 'type' => 'string' ],
						],
						'include_order_bump' => [
							'type'        => 'boolean',
							'description' => 'For checkout steps only: also insert an order-bump widget placeholder. Leave off unless an order bump has already been configured via upsert-order-bump.',
							'default'     => false,
						],
					],
					'required'   => [ 'step_id', 'headline' ],
				],
				'execute_callback'    => [ __CLASS__, 'composeStepLayout' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],

			'wpfunnels/rewrite-step-copy' => [
				'description' => 'Perform surgical copy editing on a specific element of a step page (headline, subheadline, or CTA text).',
				'category'    => 'content',
				'readonly'    => false,
				'destructive' => true,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'step_id' => [
							'type'        => 'integer',
							'description' => 'ID of the step.',
						],
						'element' => [
							'type'        => 'string',
							'enum'        => [ 'headline', 'subheadline', 'cta_text', 'guarantee_text' ],
							'description' => 'Element to update.',
						],
						'content' => [
							'type'        => 'string',
							'description' => 'New copy content.',
						],
					],
					'required'   => [ 'step_id', 'element', 'content' ],
				],
				'callback'    => [ __CLASS__, 'rewriteStepCopy' ],
			],

			'wpfunnels/generate-step-image' => [
				'label'               => __( 'Generate Step Image', 'wpfnl' ),
				'description'         => 'Generate an image from a text prompt (OpenAI DALL-E 3) and attach it to a step, either as its featured image or as a stored content image. Requires OpenAI connected as the active provider under WPFunnels → Settings → AI — other providers do not support image generation here.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id'   => [
							'type'        => 'integer',
							'description' => 'ID of the step.',
						],
						'prompt'    => [
							'type'        => 'string',
							'description' => 'Description of the image to generate.',
						],
						'placement' => [
							'type'        => 'string',
							'enum'        => [ 'featured', 'content' ],
							'default'     => 'featured',
							'description' => 'featured sets it as the step\'s featured image; content stores it for use in the page body.',
						],
						'size'      => [
							'type'        => 'string',
							'enum'        => [ '1024x1024', '1792x1024', '1024x1792' ],
							'default'     => '1024x1024',
						],
					],
					'required'   => [ 'step_id', 'prompt' ],
				],
				'execute_callback'    => [ __CLASS__, 'generateStepImage' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],

			'wpfunnels/get-step-outline' => [
				'description' => 'Get the AI-authored placeholder copy fields (headline/subheadline/cta_text/benefits) set by compose-step-page/rewrite-step-copy. Falls back to generic defaults when unset — these do NOT reflect the page\'s actual builder content. For a Gutenberg page\'s real on-page text, use wpfunnels/list-gutenberg-text instead.',
				'category'    => 'content',
				'readonly'    => true,
				'destructive' => false,
				'parameters'  => [
					'type'       => 'object',
					'properties' => [
						'step_id' => [
							'type'        => 'integer',
							'description' => 'ID of the step.',
						],
					],
					'required'   => [ 'step_id' ],
				],
				'callback'    => [ __CLASS__, 'getStepOutline' ],
			],

			'wpfunnels/detect-page-builder'   => [
				'label'               => __( 'Detect Page Builder', 'wpfnl' ),
				'description'         => 'Find which page builder authored a funnel step\'s page — Gutenberg, Elementor, Bricks, another builder, or unknown.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_step_id' => [
							'type'        => 'integer',
							'description' => 'ID of the funnel step.',
						],
					],
					'required'   => [ 'funnel_step_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'detectPageBuilder' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],

			'wpfunnels/get-funnel-step-page'  => [
				'label'               => __( 'Get Funnel Step Page', 'wpfnl' ),
				'description'         => 'Get the WordPress page connected to a funnel step: its ID, title, post type, status, and detected page builder.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'funnel_step_id' => [
							'type'        => 'integer',
							'description' => 'ID of the funnel step.',
						],
					],
					'required'   => [ 'funnel_step_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'getFunnelStepPage' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],

			'wpfunnels/list-gutenberg-text'   => [
				'label'               => __( 'List Gutenberg Page Text', 'wpfnl' ),
				'description'         => 'List the actual visible text found in a Gutenberg page\'s blocks, in reading order, each tagged with its block type. Call this BEFORE update-gutenberg-text to get the real target_text — do not guess it from get-step-outline or the step title, those are separate AI-authored placeholder fields that may not match what is actually on the page.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'page_id' => [
							'type'        => 'integer',
							'description' => 'WordPress page/post ID (from get-funnel-step-page).',
						],
					],
					'required'   => [ 'page_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'listGutenbergText' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],

			'wpfunnels/update-gutenberg-text' => [
				'label'               => __( 'Update Gutenberg Text', 'wpfnl' ),
				'description'         => 'Replace one piece of text inside a Gutenberg page\'s blocks, keeping block markup intact. Get target_text from wpfunnels/list-gutenberg-text rather than guessing — matching tolerates whitespace differences but not wrong text. Fails if the page is not Gutenberg or the target text is not found.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'page_id'     => [
							'type'        => 'integer',
							'description' => 'WordPress page/post ID (from get-funnel-step-page).',
						],
						'target_text' => [
							'type'        => 'string',
							'description' => 'Existing text to find inside the page\'s blocks, as returned by wpfunnels/list-gutenberg-text.',
						],
						'new_text'    => [
							'type'        => 'string',
							'description' => 'Replacement text.',
						],
					],
					'required'   => [ 'page_id', 'target_text', 'new_text' ],
				],
				'execute_callback'    => [ __CLASS__, 'updateGutenbergText' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
		];
	}

	/**
	 * Compose step page.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function composeStepPage( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$headline    = sanitize_text_field( $input['headline'] );
		$subheadline = isset( $input['subheadline'] ) ? sanitize_text_field( $input['subheadline'] ) : '';
		$cta_text    = isset( $input['cta_text'] ) ? sanitize_text_field( $input['cta_text'] ) : 'Get Started';
		$benefits    = isset( $input['benefits'] ) && is_array( $input['benefits'] ) ? array_map( 'sanitize_text_field', $input['benefits'] ) : [];

		update_post_meta( $step->ID, '_wpfnl_page_headline', $headline );
		if ( '' !== $subheadline ) {
			update_post_meta( $step->ID, '_wpfnl_page_subheadline', $subheadline );
		}
		update_post_meta( $step->ID, '_wpfnl_page_cta_text', $cta_text );
		if ( ! empty( $benefits ) ) {
			update_post_meta( $step->ID, '_wpfnl_page_benefits', $benefits );
		}

		return [
			'composed'    => true,
			'step_id'     => (int) $step->ID,
			'headline'    => $headline,
			'subheadline' => $subheadline,
			'cta_text'    => $cta_text,
			'benefits'    => $benefits,
		];
	}

	/**
	 * Build a real Elementor widget layout for a step, chosen from its step
	 * type, and keep the placeholder copy fields (compose-step-page) in sync
	 * with what was actually rendered.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function composeStepLayout( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$copy = self::composeStepPage( $input );
		if ( is_wp_error( $copy ) ) {
			return $copy;
		}

		$step_type          = get_post_meta( $step->ID, '_step_type', true ) ?: 'custom';
		$include_order_bump = ! empty( $input['include_order_bump'] );
		$design_tokens      = MCPHelper::getSiteDesignTokens();

		$builders = [
			'landing'  => 'buildOptinTree',
			'optin'    => 'buildOptinTree',
			'checkout' => 'buildCheckoutTree',
			'upsell'   => 'buildOfferTree',
			'downsell' => 'buildOfferTree',
			'thankyou' => 'buildThankyouTree',
		];
		$builder = isset( $builders[ $step_type ] ) ? $builders[ $step_type ] : 'buildCustomTree';

		ElementorLayoutBuilder::reset();
		$tree = self::$builder(
			$copy['headline'],
			$copy['subheadline'],
			$copy['cta_text'],
			$copy['benefits'],
			[
				'step_type'          => $step_type,
				'include_order_bump' => $include_order_bump,
				'primary_color'      => $design_tokens['primary_color'],
			]
		);

		self::writeElementorTree( $step->ID, $tree );

		return array_merge(
			$copy,
			[
				'step_type'             => $step_type,
				'elementor_data_written' => true,
				'widgets_used'          => self::collectWidgetTypes( $tree ),
			]
		);
	}

	/**
	 * Write a node tree to `_elementor_data` and the companion metas Elementor
	 * expects on a page it built itself, so the editor treats it the same as
	 * content authored by hand.
	 *
	 * @param int   $step_id Step post id.
	 * @param array $tree    Array of section nodes.
	 * @return void
	 */
	private static function writeElementorTree( $step_id, $tree ) {
		update_post_meta( $step_id, '_elementor_data', wp_slash( wp_json_encode( $tree ) ) );
		update_post_meta( $step_id, '_elementor_edit_mode', 'builder' );
		update_post_meta( $step_id, '_elementor_version', defined( 'ELEMENTOR_VERSION' ) ? ELEMENTOR_VERSION : '3.5.0' );

		// Force Elementor to regenerate its CSS cache file rather than trusting
		// a stale or absent one.
		delete_post_meta( $step_id, '_elementor_css' );
		delete_post_meta( $step_id, '_elementor_page_assets' );
	}

	/**
	 * Landing / opt-in layout: headline block, then an opt-in capture widget.
	 *
	 * @param string $headline    Headline.
	 * @param string $subheadline Subheadline.
	 * @param string $cta_text    CTA text.
	 * @param array  $benefits    Benefit bullets.
	 * @param array  $options     step_type, include_order_bump, primary_color.
	 * @return array
	 */
	private static function buildOptinTree( $headline, $subheadline, $cta_text, $benefits, $options ) {
		return [
			ElementorLayoutBuilder::section(
				[
					ElementorLayoutBuilder::column(
						array_values(
							array_filter(
								[
									ElementorLayoutBuilder::headingWidget( $headline, 'h1' ),
									'' !== $subheadline ? ElementorLayoutBuilder::textWidget( $subheadline ) : null,
									ElementorLayoutBuilder::benefitsWidget( $benefits ),
								]
							)
						)
					),
				]
			),
			ElementorLayoutBuilder::sectionFor(
				ElementorLayoutBuilder::optinWidget(
					[
						'cta_text'      => $cta_text,
						'primary_color' => $options['primary_color'],
					]
				)
			),
		];
	}

	/**
	 * Checkout layout: optional headline block, then the checkout widget
	 * (and an order-bump placeholder only when explicitly requested).
	 *
	 * @param string $headline    Headline.
	 * @param string $subheadline Subheadline.
	 * @param string $cta_text    CTA text.
	 * @param array  $benefits    Benefit bullets.
	 * @param array  $options     step_type, include_order_bump, primary_color.
	 * @return array
	 */
	private static function buildCheckoutTree( $headline, $subheadline, $cta_text, $benefits, $options ) {
		$widgets = [
			ElementorLayoutBuilder::checkoutWidget(
				[
					'cta_text'      => $cta_text,
					'subheadline'   => $subheadline,
					'primary_color' => $options['primary_color'],
				]
			),
		];

		if ( ! empty( $options['include_order_bump'] ) ) {
			$widgets[] = ElementorLayoutBuilder::orderBumpWidget();
		}

		return [
			ElementorLayoutBuilder::sectionFor( ElementorLayoutBuilder::headingWidget( $headline, 'h1' ) ),
			ElementorLayoutBuilder::section( [ ElementorLayoutBuilder::column( $widgets ) ] ),
		];
	}

	/**
	 * Upsell / downsell layout: headline block, then accept + decline offer
	 * widgets side by side — `wpfnl-offer` is single-valued per instance.
	 *
	 * @param string $headline    Headline.
	 * @param string $subheadline Subheadline.
	 * @param string $cta_text    CTA text.
	 * @param array  $benefits    Benefit bullets.
	 * @param array  $options     step_type (upsell|downsell), include_order_bump.
	 * @return array
	 */
	private static function buildOfferTree( $headline, $subheadline, $cta_text, $benefits, $options ) {
		$offer_button_type = 'downsell' === $options['step_type'] ? 'downsell' : 'upsell';

		$accept = ElementorLayoutBuilder::offerWidget(
			[
				'offer_button_type' => $offer_button_type,
				'offer_type'        => 'accept',
				'text'              => '' !== $cta_text ? $cta_text : 'Yes, Add This To My Order!',
				'primary_color'     => $options['primary_color'],
			]
		);
		$decline = ElementorLayoutBuilder::offerWidget(
			[
				'offer_button_type' => $offer_button_type,
				'offer_type'        => 'reject',
				'text'              => 'No, thanks',
			]
		);

		return [
			ElementorLayoutBuilder::section(
				[
					ElementorLayoutBuilder::column(
						array_values(
							array_filter(
								[
									ElementorLayoutBuilder::headingWidget( $headline, 'h1' ),
									'' !== $subheadline ? ElementorLayoutBuilder::textWidget( $subheadline ) : null,
									ElementorLayoutBuilder::benefitsWidget( $benefits ),
								]
							)
						)
					),
				]
			),
			ElementorLayoutBuilder::section(
				[
					ElementorLayoutBuilder::column( [ $accept ], [ '_column_size' => 50 ] ),
					ElementorLayoutBuilder::column( [ $decline ], [ '_column_size' => 50 ] ),
				]
			),
		];
	}

	/**
	 * Thank-you layout: headline block, then a next-step widget pointing the
	 * visitor at what comes after the funnel.
	 *
	 * @param string $headline    Headline.
	 * @param string $subheadline Subheadline.
	 * @param string $cta_text    CTA text.
	 * @param array  $benefits    Benefit bullets.
	 * @param array  $options     step_type, include_order_bump, primary_color.
	 * @return array
	 */
	private static function buildThankyouTree( $headline, $subheadline, $cta_text, $benefits, $options ) {
		return [
			ElementorLayoutBuilder::section(
				[
					ElementorLayoutBuilder::column(
						array_values(
							array_filter(
								[
									ElementorLayoutBuilder::headingWidget( $headline, 'h1' ),
									'' !== $subheadline ? ElementorLayoutBuilder::textWidget( $subheadline ) : null,
									ElementorLayoutBuilder::benefitsWidget( $benefits ),
								]
							)
						)
					),
				]
			),
			ElementorLayoutBuilder::sectionFor(
				ElementorLayoutBuilder::nextStepWidget(
					[
						'cta_text'      => $cta_text,
						'primary_color' => $options['primary_color'],
					]
				)
			),
		];
	}

	/**
	 * Fallback layout for `custom` or unrecognized step types: headline block
	 * only, no WPFunnels-specific widget.
	 *
	 * @param string $headline    Headline.
	 * @param string $subheadline Subheadline.
	 * @param string $cta_text    CTA text.
	 * @param array  $benefits    Benefit bullets.
	 * @param array  $options     step_type, include_order_bump, primary_color.
	 * @return array
	 */
	private static function buildCustomTree( $headline, $subheadline, $cta_text, $benefits, $options ) {
		return [
			ElementorLayoutBuilder::section(
				[
					ElementorLayoutBuilder::column(
						array_values(
							array_filter(
								[
									ElementorLayoutBuilder::headingWidget( $headline, 'h1' ),
									'' !== $subheadline ? ElementorLayoutBuilder::textWidget( $subheadline ) : null,
									ElementorLayoutBuilder::benefitsWidget( $benefits ),
								]
							)
						)
					),
				]
			),
		];
	}

	/**
	 * Recursively collect distinct `widgetType` values from a node tree.
	 *
	 * @param array $nodes Section/column/widget nodes.
	 * @return array
	 */
	private static function collectWidgetTypes( $nodes ) {
		$types = [];

		foreach ( $nodes as $node ) {
			if ( isset( $node['widgetType'] ) ) {
				$types[ $node['widgetType'] ] = true;
			}
			if ( ! empty( $node['elements'] ) ) {
				foreach ( self::collectWidgetTypes( $node['elements'] ) as $type ) {
					$types[ $type ] = true;
				}
			}
		}

		return array_keys( $types );
	}

	/**
	 * Rewrite copy for a specific element.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function rewriteStepCopy( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$element = sanitize_text_field( $input['element'] );
		$content = sanitize_text_field( $input['content'] );

		update_post_meta( $step->ID, '_wpfnl_page_' . $element, $content );

		return [
			'updated' => true,
			'step_id' => (int) $step->ID,
			'element' => $element,
			'content' => $content,
		];
	}

	/**
	 * Generate an image from a prompt and attach it to a step.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function generateStepImage( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$prompt = isset( $input['prompt'] ) ? sanitize_textarea_field( $input['prompt'] ) : '';
		if ( '' === $prompt ) {
			return MCPHelper::error( 'missing_prompt', 'Describe the image to generate.' );
		}

		if ( 'openai' !== AISettings::getActiveProvider() ) {
			return MCPHelper::error( 'openai_required', 'Image generation needs OpenAI connected as the active AI provider (WPFunnels → Settings → AI).' );
		}

		$provider = AIInit::makeProvider( 'openai', AISettings::getApiKey( 'openai' ) );
		$size     = isset( $input['size'] ) && in_array( $input['size'], [ '1024x1024', '1792x1024', '1024x1792' ], true ) ? $input['size'] : '1024x1024';

		$result = $provider->generateImage( $prompt, $size );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$b64 = isset( $result['data'][0]['b64_json'] ) ? $result['data'][0]['b64_json'] : '';
		if ( '' === $b64 ) {
			return MCPHelper::error( 'image_generation_failed', 'The provider did not return image data.' );
		}

		$upload = wp_upload_bits( 'wpfnl-ai-' . $step->ID . '-' . uniqid() . '.png', null, base64_decode( $b64 ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
		if ( ! empty( $upload['error'] ) ) {
			return MCPHelper::error( 'upload_failed', $upload['error'] );
		}

		$attachment_id = wp_insert_attachment(
			[
				'post_mime_type' => 'image/png',
				'post_title'     => $prompt,
				'post_status'    => 'inherit',
			],
			$upload['file']
		);
		if ( is_wp_error( $attachment_id ) ) {
			return $attachment_id;
		}

		require_once ABSPATH . 'wp-admin/includes/image.php';
		wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $upload['file'] ) );

		$placement = isset( $input['placement'] ) && 'content' === $input['placement'] ? 'content' : 'featured';
		if ( 'featured' === $placement ) {
			set_post_thumbnail( $step->ID, $attachment_id );
		} else {
			update_post_meta( $step->ID, '_wpfnl_page_image_id', $attachment_id );
		}

		return [
			'generated'     => true,
			'step_id'       => (int) $step->ID,
			'attachment_id' => $attachment_id,
			'url'           => wp_get_attachment_url( $attachment_id ),
			'placement'     => $placement,
		];
	}

	/**
	 * Get step outline.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getStepOutline( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		return [
			'step_id'     => (int) $step->ID,
			'step_title'  => $step->post_title,
			'step_type'   => get_post_meta( $step->ID, '_step_type', true ),
			'headline'    => get_post_meta( $step->ID, '_wpfnl_page_headline', true ) ?: $step->post_title,
			'subheadline' => get_post_meta( $step->ID, '_wpfnl_page_subheadline', true ) ?: '',
			'cta_text'    => get_post_meta( $step->ID, '_wpfnl_page_cta_text', true ) ?: 'Buy Now',
			'benefits'    => get_post_meta( $step->ID, '_wpfnl_page_benefits', true ) ?: [],
			'note'        => 'These are AI-authored placeholder fields, not necessarily the page\'s live builder content — they read as generic defaults when unset. For a Gutenberg page\'s actual on-page text, call wpfunnels/list-gutenberg-text instead.',
		];
	}

	/**
	 * Detect the page builder behind a funnel step's page.
	 *
	 * A step post IS its own page/post in WPFunnels, so the step id and the
	 * page id are the same value — no separate lookup needed.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function detectPageBuilder( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['funnel_step_id'] ) ? $input['funnel_step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		return [
			'page_id' => (int) $step->ID,
			'builder' => MCPHelper::detectPageBuilder( $step->ID ),
		];
	}

	/**
	 * Get the page connected to a funnel step, with basic info and its
	 * detected builder.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getFunnelStepPage( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['funnel_step_id'] ) ? $input['funnel_step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		return [
			'funnel_step_id' => (int) $step->ID,
			'page_id'        => (int) $step->ID,
			'page_title'     => $step->post_title,
			'post_type'      => $step->post_type,
			'status'         => $step->post_status,
			'builder'        => MCPHelper::detectPageBuilder( $step->ID ),
		];
	}

	/**
	 * List the actual visible text sitting in a Gutenberg page's blocks, in
	 * reading order, so the model has real strings to pass to
	 * update-gutenberg-text instead of guessing from unrelated placeholder
	 * fields (get-step-outline) or the step title.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function listGutenbergText( $input = [] ) {
		$page_id = isset( $input['page_id'] ) ? (int) $input['page_id'] : 0;
		$post    = $page_id ? get_post( $page_id ) : null;

		if ( ! $post ) {
			return MCPHelper::error( 'page_not_found', sprintf( 'No page found with ID %d.', $page_id ) );
		}

		$builder = MCPHelper::detectPageBuilder( $page_id );
		if ( 'gutenberg' !== $builder ) {
			return MCPHelper::error(
				'unsupported_builder',
				sprintf( 'Page %d uses "%s", not Gutenberg. This tool only reads Gutenberg block content.', $page_id, $builder ),
				[
					'page_id' => $page_id,
					'builder' => $builder,
				]
			);
		}

		$blocks     = parse_blocks( $post->post_content );
		$text_nodes = [];
		self::collectBlockText( $blocks, $text_nodes );

		return [
			'page_id'    => $page_id,
			'text_nodes' => $text_nodes,
			'count'      => count( $text_nodes ),
		];
	}

	/**
	 * Recursively collect non-empty visible text per block, tag-stripped.
	 *
	 * A block that wraps other blocks (group, columns, column…) carries no
	 * text of its own in its own `innerHTML` — its children are walked
	 * separately — so it's naturally skipped by the empty-text check.
	 *
	 * Displayed text is cleaned up (entities decoded, zero-width characters
	 * dropped) so the model isn't asked to retype invisible junk it can't
	 * see — update-gutenberg-text's lenient matching reconciles that cleanup
	 * against whatever the raw stored HTML actually contains.
	 *
	 * @param array $blocks     Blocks (parse_blocks() output).
	 * @param array $text_nodes Accumulator, by reference.
	 * @return void
	 */
	private static function collectBlockText( $blocks, &$text_nodes ) {
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['blockName'] ) && ! empty( $block['innerHTML'] ) ) {
				$text = self::cleanDisplayText( wp_strip_all_tags( $block['innerHTML'] ) );
				if ( '' !== $text ) {
					$text_nodes[] = [
						'block_type' => $block['blockName'],
						'text'       => $text,
					];
				}
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				self::collectBlockText( $block['innerBlocks'], $text_nodes );
			}
		}
	}

	/**
	 * Decode entities and strip zero-width characters for display, so the
	 * model sees the same text a visitor would rather than raw markup noise
	 * (`&nbsp;`, `​`) that happens to be baked into the stored HTML.
	 *
	 * @param string $text Raw tag-stripped text.
	 * @return string
	 */
	private static function cleanDisplayText( $text ) {
		$text = html_entity_decode( $text, ENT_QUOTES, 'UTF-8' );
		$text = preg_replace( '/[\x{200B}\x{200C}\x{200D}\x{FEFF}]/u', '', $text );
		return trim( $text );
	}

	/**
	 * Replace one piece of text inside a Gutenberg page, without disturbing
	 * block structure.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function updateGutenbergText( $input = [] ) {
		$page_id = isset( $input['page_id'] ) ? (int) $input['page_id'] : 0;
		$post    = $page_id ? get_post( $page_id ) : null;

		if ( ! $post ) {
			return MCPHelper::error( 'page_not_found', sprintf( 'No page found with ID %d.', $page_id ) );
		}

		$builder = MCPHelper::detectPageBuilder( $page_id );
		if ( 'gutenberg' !== $builder ) {
			return MCPHelper::error(
				'unsupported_builder',
				sprintf( 'Page %d uses "%s", not Gutenberg. This tool only edits Gutenberg block content.', $page_id, $builder ),
				[
					'page_id' => $page_id,
					'builder' => $builder,
				]
			);
		}

		$target_text = isset( $input['target_text'] ) ? (string) $input['target_text'] : '';
		if ( '' === trim( $target_text ) ) {
			return MCPHelper::error( 'missing_target_text', 'Provide the exact text to find inside the page.' );
		}
		$new_text = isset( $input['new_text'] ) ? wp_kses_post( $input['new_text'] ) : '';

		$blocks       = parse_blocks( $post->post_content );
		$replacements = self::replaceTextInBlocks( $blocks, $target_text, $new_text );

		// Exact match failed — retry tolerating whitespace differences (line
		// breaks/indentation the model can't see) before giving up, since that's
		// the most common reason correct-looking text doesn't match verbatim.
		if ( 0 === $replacements ) {
			$replacements = self::replaceTextInBlocks( $blocks, $target_text, $new_text, true );
		}

		if ( 0 === $replacements ) {
			return [
				'success' => false,
				'page_id' => $page_id,
				'updated' => false,
				'message' => 'Target text was not found on this page. Call wpfunnels/list-gutenberg-text to see the exact text available, then retry with one of those strings.',
			];
		}

		$updated = wp_update_post(
			[
				'ID'           => $page_id,
				'post_content' => serialize_blocks( $blocks ),
			],
			true
		);

		if ( is_wp_error( $updated ) ) {
			return $updated;
		}

		return [
			'success' => true,
			'page_id' => $page_id,
			'updated' => true,
			'message' => sprintf( 'Replaced %d occurrence(s) of the target text.', $replacements ),
		];
	}

	/**
	 * Recursively replace text inside parsed blocks' `innerContent` chunks,
	 * leaving block attrs and comment delimiters untouched — only the
	 * rendered HTML fragments are edited, so unrelated blocks and unrelated
	 * text within a matching block are never touched.
	 *
	 * @param array  $blocks  Blocks, by reference (parse_blocks() output).
	 * @param string $target  Text to find.
	 * @param string $replace Replacement text.
	 * @param bool   $fuzzy   Tolerate whitespace/`&nbsp;`, quote-glyph, and
	 *                        zero-width-character differences instead of
	 *                        requiring an exact substring — see
	 *                        fuzzyTextPattern().
	 * @return int Number of occurrences replaced.
	 */
	private static function replaceTextInBlocks( &$blocks, $target, $replace, $fuzzy = false ) {
		$count   = 0;
		$pattern = $fuzzy ? self::fuzzyTextPattern( $target ) : null;

		foreach ( $blocks as &$block ) {
			if ( ! empty( $block['innerContent'] ) && is_array( $block['innerContent'] ) ) {
				foreach ( $block['innerContent'] as &$chunk ) {
					if ( ! is_string( $chunk ) ) {
						continue;
					}
					if ( $fuzzy ) {
						if ( null === $pattern ) {
							continue;
						}
						// preg_replace_callback, not preg_replace, so $replace's
						// literal content (e.g. a stray '$1' or backslash) is
						// never reinterpreted as a backreference.
						$replaced = preg_replace_callback(
							$pattern,
							static function () use ( $replace ) {
								return $replace;
							},
							$chunk,
							-1,
							$matched
						);
						if ( null !== $replaced ) {
							$chunk  = $replaced;
							$count += $matched;
						}
					} elseif ( false !== strpos( $chunk, $target ) ) {
						$count += substr_count( $chunk, $target );
						$chunk  = str_replace( $target, $replace, $chunk );
					}
				}
				unset( $chunk );

				$block['innerHTML'] = implode( '', array_filter( $block['innerContent'], 'is_string' ) );
			}

			if ( ! empty( $block['innerBlocks'] ) && is_array( $block['innerBlocks'] ) ) {
				$count += self::replaceTextInBlocks( $block['innerBlocks'], $target, $replace, $fuzzy );
			}
		}
		unset( $block );

		return $count;
	}

	/**
	 * Build a case-sensitive regex that matches `$target` against the raw
	 * stored HTML while tolerating the exact kinds of noise
	 * list-gutenberg-text cleans up for display, since that's where the
	 * model's copy of the text comes from:
	 *   - zero-width characters (U+200B/200C/200D/FEFF) anywhere, incl.
	 *     between every character — list-gutenberg-text strips these, but
	 *     they may sit mid-word in the stored HTML;
	 *   - straight vs curly quote glyphs for `'` and `"`;
	 *   - a run of whitespace matching either a literal space/newline or a
	 *     literal `&nbsp;`/`\xA0` — covers both HTML line-wrapping and the
	 *     stored-as-text-not-a-real-space `&nbsp;` artifacts some templates
	 *     have baked into their copy.
	 *
	 * Built character-by-character (not word-by-word) since the mismatch can
	 * fall inside what looks like a single word, e.g. an apostrophe glyph.
	 *
	 * @param string $target Text to find (as shown by list-gutenberg-text).
	 * @return string|null Regex pattern, or null if target is empty.
	 */
	private static function fuzzyTextPattern( $target ) {
		$target = trim( $target );
		if ( '' === $target ) {
			return null;
		}

		$chars = preg_split( '//u', $target, -1, PREG_SPLIT_NO_EMPTY );
		if ( empty( $chars ) ) {
			return null;
		}

		$quote_classes = [
			"'" => "['’‘`´]",
			'"' => '["“”„]',
		];

		$zwsp    = '[\x{200B}\x{200C}\x{200D}\x{FEFF}]*';
		$pattern = $zwsp;

		foreach ( $chars as $char ) {
			if ( isset( $quote_classes[ $char ] ) ) {
				$pattern .= $quote_classes[ $char ];
			} elseif ( preg_match( '/\s/u', $char ) ) {
				$pattern .= '(?:\s|&nbsp;|\x{00A0})+';
			} else {
				$pattern .= preg_quote( $char, '/' );
			}
			$pattern .= $zwsp;
		}

		return '/' . $pattern . '/u';
	}
}
