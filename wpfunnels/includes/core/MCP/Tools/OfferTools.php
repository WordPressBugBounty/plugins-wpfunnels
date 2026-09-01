<?php
/**
 * OfferTools — checkout-side order bump abilities (Free domain).
 *
 * Order bumps live as a single `order-bump-settings` post meta array on the
 * checkout step, one entry per bump (keyed by numeric index). This wraps the
 * exact same meta model and type-factory hooks
 * `includes/core/rest-api/Controllers/class-orderbump-controller.php` uses,
 * so the copilot and the checkout step editor UI never drift apart.
 *
 * Post-purchase upsell/downsell offers are a separate, Pro-only domain — see
 * `wpfunnels-pro/includes/core/mcp/Tools/PostPurchaseOfferTools.php`.
 *
 * @package WPFunnels\MCP
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Helpers\MCPHelper;
use WPFunnels\Rest\Controllers\OrderBumpController;

/**
 * Class OfferTools
 */
class OfferTools {

	/**
	 * Post meta key order bump settings are stored under.
	 */
	private const META_KEY = 'order-bump-settings';

	/**
	 * Ability definitions for this domain.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/get-order-bump'          => [
				'label'               => __( 'Get Order Bump', 'wpfnl' ),
				'description'         => 'List the order bumps configured on a checkout step: product, quantity, discount (including any attached coupon) and any conditional display rules, plus whether each is enabled.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id' => [
							'type'        => 'integer',
							'description' => 'Checkout step post ID.',
						],
					],
					'required'   => [ 'step_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'getOrderBump' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/upsert-order-bump'       => [
				'label'               => __( 'Add or Update Order Bump', 'wpfnl' ),
				'description'         => 'Add a new order bump to a checkout step, or update an existing one by index (from get-order-bump). Other order bumps on the same step are left untouched — this is a read-modify-write on one entry, not a full replace.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id'        => [
							'type'        => 'integer',
							'description' => 'Checkout step post ID.',
						],
						'index'          => [
							'type'        => 'integer',
							'description' => 'Index of an existing order bump to update, from get-order-bump. Omit to add a new one.',
						],
						'product_id'     => [
							'type'        => 'integer',
							'description' => 'WooCommerce product ID offered as the bump.',
						],
						'quantity'       => [
							'type'        => 'integer',
							'description' => 'Quantity added when the bump is accepted.',
							'default'     => 1,
						],
						'discount_type'  => [
							'type'        => 'string',
							'description' => 'How the discount is applied. "coupon" attaches an existing WooCommerce coupon (find it first with wpfunnels/search-coupons) rather than a raw percentage/price.',
							'enum'        => [ 'discount-percentage', 'discount-price', 'coupon', 'none' ],
						],
						'discount_value' => [
							'type'        => 'number',
							'description' => 'Discount percentage (0-100) or fixed amount, matching discount_type. Not used when discount_type is "coupon" — use coupon_code instead.',
						],
						'coupon_code'    => [
							'type'        => 'string',
							'description' => 'Code of an existing WooCommerce coupon to attach when discount_type is "coupon". Find it with wpfunnels/search-coupons — WPFunnels never creates coupons itself, only attaches existing ones.',
						],
						'enabled'        => [
							'type'        => 'boolean',
							'description' => 'Whether the bump is active.',
							'default'     => true,
						],
						'conditions'     => [
							'type'        => 'object',
							'description' => 'Conditional display/targeting rules for the bump (stored as the entry\'s ruleSettings). When enabled, the bump is only shown if the cart matches the rule groups.',
							'properties'  => [
								'enabled'    => [
									'type'        => 'boolean',
									'description' => 'Whether conditional display is active. When false (default) the bump always shows.',
								],
								'ruleGroups' => [
									'type'        => 'array',
									'description' => 'Groups of conditions, OR-ed together. Each group\'s own conditions are AND-ed together.',
									'items'       => [
										'type'       => 'object',
										'properties' => [
											'name'       => [
												'type'        => 'string',
												'description' => 'Label for the rule group.',
											],
											'conditions' => [
												'type'  => 'array',
												'items' => [
													'type'       => 'object',
													'properties' => [
														'field'    => [
															'type'        => 'string',
															'description' => 'What the condition matches against.',
															'enum'        => [
																'no_rules',
																'cart_total',
																'cart_subtotal',
																'cart_item_count',
																'cart_items_quantity',
																'cart_items',
																'item_categories',
																'item_tags',
																'cart_coupons',
																'shipping_method',
																'shipping_country',
																'billing_country',
																'checkout_page',
															],
														],
														'operator' => [
															'type'        => 'string',
															'description' => 'Comparison operator. Numeric fields use ==, !=, >, <, >=, <=; list fields (cart_items, item_categories, item_tags, cart_coupons, etc.) use matches_any, matches_all, matches_none.',
														],
														'value'    => [
															'description' => 'Comparison value: a number/string for numeric fields, or an array of IDs/codes for list fields.',
														],
													],
													'required'   => [ 'field', 'operator' ],
												],
											],
										],
									],
								],
							],
						],
					],
					'required'   => [ 'step_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'upsertOrderBump' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/search-coupons'           => [
				'label'               => __( 'Search Coupons', 'wpfnl' ),
				'description'         => 'Search existing WooCommerce coupons by code, for attaching to an order bump as its discount mechanism (discount_type "coupon" on wpfunnels/upsert-order-bump). WPFunnels never creates coupons — this only finds ones that already exist.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'search' => [
							'type'        => 'string',
							'description' => 'Search term matched against coupon codes.',
						],
					],
					'required'   => [ 'search' ],
				],
				'execute_callback'    => [ __CLASS__, 'searchCoupons' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/delete-order-bump'       => [
				'label'               => __( 'Delete Order Bump', 'wpfnl' ),
				'description'         => 'Remove one order bump entry from a checkout step by index. Other order bumps on the step are left untouched.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id' => [
							'type'        => 'integer',
							'description' => 'Checkout step post ID.',
						],
						'index'   => [
							'type'        => 'integer',
							'description' => 'Index of the order bump to remove, from get-order-bump.',
						],
					],
					'required'   => [ 'step_id', 'index' ],
				],
				'execute_callback'    => [ __CLASS__, 'deleteOrderBump' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/calculate-offer-discount' => [
				'label'               => __( 'Calculate Offer Discount', 'wpfnl' ),
				'description'         => 'Calculate the discounted price for a given discount type/value against a product price — use before proposing a discount so the number shown to the site owner is accurate.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'discount_type'  => [
							'type'        => 'string',
							'enum'        => [ 'discount-percentage', 'discount-price' ],
						],
						'discount_value' => [ 'type' => 'number' ],
						'product_price'  => [ 'type' => 'number' ],
					],
					'required'   => [ 'discount_type', 'discount_value', 'product_price' ],
				],
				'execute_callback'    => [ __CLASS__, 'calculateOfferDiscount' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
		];
	}

	/**
	 * Read the raw order-bump-settings array for a step.
	 *
	 * @param int $step_id Step id.
	 * @return array
	 */
	private static function readSettings( $step_id ) {
		$settings = get_post_meta( $step_id, self::META_KEY, true );
		return is_array( $settings ) ? $settings : [];
	}

	/**
	 * Persist the order-bump-settings array for a step, running it through the
	 * same funnel-type factory hook the REST controller applies, and firing
	 * the same actions so downstream code (webhooks, GBF sync) still fires.
	 *
	 * @param int   $step_id  Step id.
	 * @param array $settings Full settings array.
	 * @return void
	 */
	private static function writeSettings( $step_id, array $settings ) {
		$funnel_id = (int) get_post_meta( $step_id, '_funnel_id', true );
		$type      = get_post_meta( $funnel_id, '_wpfnl_funnel_type', true );
		$type      = $type ? $type : 'wc';

		if ( class_exists( '\Wpfnl_Controller_Type_Factory' ) ) {
			$class_object = \Wpfnl_Controller_Type_Factory::build( $type );
			if ( $class_object && method_exists( $class_object, 'update_ob_settings' ) ) {
				$settings = $class_object->update_ob_settings( $settings );
			}
		}

		update_post_meta( $step_id, self::META_KEY, $settings );
		do_action( 'wpfunnels/after_save_order_bump_data', $step_id, $settings );
		do_action( 'wpfunnels_order_bump_added', $funnel_id, $step_id );
	}

	/**
	 * Get order bumps for a step.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getOrderBump( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$step_id  = (int) $step->ID;
		$settings = self::readSettings( $step_id );

		$bumps = [];
		foreach ( $settings as $index => $entry ) {
			$bumps[] = [
				'index'          => (int) $index,
				'enabled'        => ! empty( $entry['isEnabled'] ),
				'product_id'     => isset( $entry['product'] ) ? (int) $entry['product'] : 0,
				'quantity'       => isset( $entry['quantity'] ) ? (int) $entry['quantity'] : 1,
				'discount_type'  => isset( $entry['discount_type'] ) ? $entry['discount_type'] : '',
				'discount_value' => isset( $entry['discount_value'] ) ? $entry['discount_value'] : 0,
				'coupon_code'    => isset( $entry['coupon_code'] ) ? $entry['coupon_code'] : ( isset( $entry['couponName'] ) ? $entry['couponName'] : '' ),
				'conditions'     => isset( $entry['ruleSettings'] ) ? $entry['ruleSettings'] : [],
			];
		}

		return [
			'step_id'    => $step_id,
			'funnel_id'  => (int) get_post_meta( $step_id, '_funnel_id', true ),
			'order_bumps' => $bumps,
			'positions'  => method_exists( '\WPFunnels\Wpfnl_functions', 'supported_orderbump_position' )
				? \WPFunnels\Wpfnl_functions::supported_orderbump_position( $step_id )
				: [],
		];
	}

	/**
	 * Add or update one order bump entry.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function upsertOrderBump( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$step_id  = (int) $step->ID;
		$settings = self::readSettings( $step_id );

		$index = isset( $input['index'] ) ? (int) $input['index'] : null;
		$entry = ( null !== $index && isset( $settings[ $index ] ) ) ? $settings[ $index ] : [];

		if ( array_key_exists( 'product_id', $input ) ) {
			$entry['product'] = (int) $input['product_id'];
		}
		if ( array_key_exists( 'quantity', $input ) ) {
			$entry['quantity'] = max( 1, (int) $input['quantity'] );
		} elseif ( ! isset( $entry['quantity'] ) ) {
			$entry['quantity'] = 1;
		}
		if ( array_key_exists( 'discount_type', $input ) ) {
			$entry['discount_type'] = sanitize_text_field( (string) $input['discount_type'] );
		}
		if ( array_key_exists( 'discount_value', $input ) ) {
			$entry['discount_value'] = (float) $input['discount_value'];
		}
		if ( array_key_exists( 'coupon_code', $input ) ) {
			$coupon_code            = sanitize_text_field( (string) $input['coupon_code'] );
			$entry['coupon_code']   = $coupon_code;
			// Mirrors the key the checkout-step admin UI (Coupon.vue) itself
			// writes to the entry, so a non-MCP save of this bump still shows
			// the attached coupon's name.
			$entry['couponName']    = $coupon_code;
		}
		if ( array_key_exists( 'conditions', $input ) ) {
			$entry['ruleSettings'] = is_array( $input['conditions'] ) ? $input['conditions'] : [];
		}
		if ( array_key_exists( 'enabled', $input ) ) {
			$entry['isEnabled'] = (bool) $input['enabled'];
		} elseif ( ! isset( $entry['isEnabled'] ) ) {
			$entry['isEnabled'] = true;
		}

		if ( empty( $entry['product'] ) ) {
			return MCPHelper::error( 'missing_product', 'An order bump needs a product_id. Use wpfunnels/search-products to find one.' );
		}

		if ( null !== $index && isset( $settings[ $index ] ) ) {
			$settings[ $index ] = $entry;
			$action             = 'updated';
		} else {
			$settings[] = $entry;
			$index      = array_key_last( $settings );
			$action     = 'created';
		}

		self::writeSettings( $step_id, $settings );

		return [
			'success' => true,
			'action'  => $action,
			'step_id' => $step_id,
			'index'   => $index,
			'entry'   => $entry,
		];
	}

	/**
	 * Remove one order bump entry.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function deleteOrderBump( $input = [] ) {
		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$step_id  = (int) $step->ID;
		$index    = isset( $input['index'] ) ? (int) $input['index'] : -1;
		$settings = self::readSettings( $step_id );

		if ( ! isset( $settings[ $index ] ) ) {
			return MCPHelper::error( 'order_bump_not_found', sprintf( 'No order bump at index %d on step %d.', $index, $step_id ) );
		}

		unset( $settings[ $index ] );
		$settings = array_values( $settings );

		self::writeSettings( $step_id, $settings );

		return [
			'success' => true,
			'step_id' => $step_id,
			'deleted_index' => $index,
			'remaining'      => count( $settings ),
		];
	}

	/**
	 * Search existing WooCommerce coupons by code.
	 *
	 * Reimplements the query core of
	 * `admin/modules/steps/checkout/class-wpfnl-checkout.php::fetch_coupons()`
	 * (its AJAX action `order_bump_search_coupons`, used by Coupon.vue) without
	 * the `$_GET`/`check_ajax_referer()` plumbing that method needs as an AJAX
	 * handler.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function searchCoupons( $input = [] ) {
		if ( ! function_exists( 'wc_get_coupon_types' ) ) {
			return MCPHelper::error( 'woocommerce_inactive', 'WooCommerce must be active to search coupons.' );
		}

		$term = isset( $input['search'] ) ? sanitize_text_field( (string) $input['search'] ) : '';
		if ( '' === $term ) {
			return [
				'search'  => $term,
				'coupons' => [],
			];
		}

		$args = [
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'asc',
			'post_type'      => 'shop_coupon',
			'post_status'    => 'publish',
			's'              => $term,
		];

		$coupons        = get_posts( $args );
		$discount_types = wc_get_coupon_types();
		$results        = [];

		if ( $coupons ) {
			foreach ( $coupons as $coupon ) {
				$discount_type = get_post_meta( $coupon->ID, 'discount_type', true );
				if ( empty( $discount_types[ $discount_type ] ) ) {
					continue;
				}

				$results[] = [
					'id'            => (int) $coupon->ID,
					'code'          => $coupon->post_title,
					'amount'        => get_post_meta( $coupon->ID, 'coupon_amount', true ),
					'discount_type' => $discount_type,
				];
			}
		}

		return [
			'search'  => $term,
			'coupons' => $results,
		];
	}

	/**
	 * Calculate a discounted price.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function calculateOfferDiscount( $input = [] ) {
		$discount_type  = isset( $input['discount_type'] ) ? (string) $input['discount_type'] : '';
		$discount_value = isset( $input['discount_value'] ) ? (float) $input['discount_value'] : 0;
		$product_price  = isset( $input['product_price'] ) ? (float) $input['product_price'] : 0;

		if ( ! class_exists( '\WPFunnels\Rest\Controllers\OrderBumpController' ) ) {
			return MCPHelper::error( 'controller_unavailable', 'The order bump controller is not available.' );
		}

		$controller = new OrderBumpController();
		$price      = $controller->calculate_custom_price( $discount_type, $discount_value, $product_price );

		return [
			'original_price'   => $product_price,
			'discounted_price' => (float) $price,
			'savings'          => round( $product_price - (float) $price, 2 ),
		];
	}
}
