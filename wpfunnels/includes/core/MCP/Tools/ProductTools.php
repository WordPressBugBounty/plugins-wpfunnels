<?php
/**
 * ProductTools — WooCommerce product lookup and step assignment.
 *
 * Product IDs are never invented: the model resolves them here first, and
 * assignment re-validates every ID against WooCommerce before writing.
 *
 * @package WPFunnels\MCP
 * @since 3.13.0
 */

namespace WPFunnels\MCP\Tools;

defined( 'ABSPATH' ) || exit;

use WPFunnels\MCP\Helpers\MCPHelper;
use WPFunnels\Wpfnl_functions;

/**
 * Class ProductTools
 */
class ProductTools {

	/**
	 * Ability definitions for this domain.
	 *
	 * @return array
	 */
	public static function definitions() {
		return [
			'wpfunnels/search-products'          => [
				'label'               => __( 'Search Products', 'wpfnl' ),
				'description'         => 'Search WooCommerce products by name or SKU and return id, name, type, price, stock and rating. Always resolve a product name to an ID here before assigning it to a step.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => array_merge(
						[
							'search'         => [
								'type'        => 'string',
								'description' => 'Name or SKU fragment. Omit to list best sellers.',
							],
							'include_variations' => [
								'type'        => 'boolean',
								'description' => 'Expand variable products into their variations.',
								'default'     => false,
							],
						],
						MCPHelper::paginationSchema()
					),
				],
				'execute_callback'    => [ __CLASS__, 'searchProducts' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
			'wpfunnels/assign-products-to-step' => [
				'label'               => __( 'Assign Products To Step', 'wpfnl' ),
				'description'         => 'Set the products on a checkout, upsell or downsell step, with an optional percentage discount. This REPLACES the step\'s existing products, so pass the complete list. Only these three step types can hold products.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'step_id'  => [
							'type'        => 'integer',
							'description' => 'Step post ID (checkout, upsell or downsell).',
						],
						'products' => [
							'type'        => 'array',
							'description' => 'Complete product list for this step.',
							'items'       => [
								'type'       => 'object',
								'properties' => [
									'product_id' => [
										'type'        => 'integer',
										'description' => 'WooCommerce product or variation ID from search-products.',
									],
									'quantity'   => [
										'type'        => 'integer',
										'description' => 'Quantity.',
										'minimum'     => 1,
										'default'     => 1,
									],
								],
								'required'   => [ 'product_id' ],
							],
						],
						'discount_percentage' => [
							'type'        => 'number',
							'description' => 'Percentage off the regular price for this step. 0 means full price. Typical starting points: checkout 0, order bump 15, upsell 15, downsell 20.',
							'minimum'     => 0,
							'maximum'     => 100,
						],
					],
					'required'   => [ 'step_id', 'products' ],
				],
				'execute_callback'    => [ __CLASS__, 'assignProductsToStep' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'destructive' ],
			],
			'wpfunnels/get-product'             => [
				'label'               => __( 'Get Product', 'wpfnl' ),
				'description'         => 'Full detail for one WooCommerce product by ID: name, type, price, stock and rating. Use search-products first if you only have a name.',
				'input_schema'        => [
					'type'       => 'object',
					'properties' => [
						'product_id' => [
							'type'        => 'integer',
							'description' => 'WooCommerce product or variation ID.',
						],
					],
					'required'   => [ 'product_id' ],
				],
				'execute_callback'    => [ __CLASS__, 'getProduct' ],
				'permission_callback' => MCPHelper::currentUserCan(),
				'annotations'         => [ 'readonly' ],
			],
		];
	}

	/**
	 * Get a single product.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function getProduct( $input = [] ) {
		if ( ! Wpfnl_functions::is_wc_active() || ! function_exists( 'wc_get_product' ) ) {
			return MCPHelper::error( 'woocommerce_inactive', 'WooCommerce is not active on this site.' );
		}

		$product_id = isset( $input['product_id'] ) ? (int) $input['product_id'] : 0;
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product ) {
			return MCPHelper::error(
				'product_not_found',
				sprintf( 'No product found with ID %d. Use wpfunnels/search-products to find one.', $product_id )
			);
		}

		return self::formatProduct( $product );
	}

	/**
	 * Search products.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function searchProducts( $input = [] ) {
		if ( ! Wpfnl_functions::is_wc_active() || ! function_exists( 'wc_get_products' ) ) {
			return MCPHelper::error(
				'woocommerce_inactive',
				'WooCommerce is not active on this site, so there are no products to search.'
			);
		}

		$per_page = MCPHelper::perPage( isset( $input['per_page'] ) ? $input['per_page'] : 0 );
		$page     = max( 1, isset( $input['page'] ) ? (int) $input['page'] : 1 );
		$search   = isset( $input['search'] ) ? trim( (string) $input['search'] ) : '';

		$args = [
			'status'   => 'publish',
			'limit'    => $per_page,
			'page'     => $page,
			'paginate' => true,
			'orderby'  => '' !== $search ? 'relevance' : 'popularity',
			'order'    => 'DESC',
		];

		if ( '' !== $search ) {
			$args['s'] = $search;
		}

		$results = wc_get_products( $args );
		$items   = [];

		foreach ( $results->products as $product ) {
			$items[] = self::formatProduct( $product );

			if ( ! empty( $input['include_variations'] ) && $product->is_type( 'variable' ) ) {
				foreach ( $product->get_children() as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( $variation ) {
						$items[] = self::formatProduct( $variation, $product->get_name() );
					}
				}
			}
		}

		return MCPHelper::paginate( $items, (int) $results->total, $page, $per_page );
	}

	/**
	 * Replace the products attached to a step.
	 *
	 * @param array $input Tool input.
	 * @return array|\WP_Error
	 */
	public static function assignProductsToStep( $input = [] ) {
		if ( ! Wpfnl_functions::is_wc_active() || ! function_exists( 'wc_get_product' ) ) {
			return MCPHelper::error( 'woocommerce_inactive', 'WooCommerce is not active, so products cannot be attached.' );
		}

		$step = MCPHelper::requireStep( isset( $input['step_id'] ) ? $input['step_id'] : 0 );
		if ( is_wp_error( $step ) ) {
			return $step;
		}

		$step_id   = (int) $step->ID;
		$step_type = (string) get_post_meta( $step_id, '_step_type', true );

		if ( ! MCPHelper::stepTypeHoldsProducts( $step_type ) ) {
			return MCPHelper::error(
				'step_type_holds_no_products',
				sprintf(
					'A %s step cannot hold products. Only checkout, upsell and downsell steps can.',
					$step_type ? $step_type : 'unknown'
				),
				[ 'step_type' => $step_type ]
			);
		}

		$requested = isset( $input['products'] ) && is_array( $input['products'] ) ? $input['products'] : [];
		if ( empty( $requested ) ) {
			return MCPHelper::error( 'missing_products', 'Pass at least one product. To clear a step, remove it instead.' );
		}

		$resolved = [];
		$rejected = [];

		foreach ( $requested as $entry ) {
			$product_id = isset( $entry['product_id'] ) ? (int) $entry['product_id'] : 0;
			$product    = $product_id ? wc_get_product( $product_id ) : null;

			if ( ! $product ) {
				$rejected[] = [
					'product_id' => $product_id,
					'reason'     => 'No WooCommerce product exists with this ID.',
				];
				continue;
			}

			if ( ! $product->is_purchasable() ) {
				$rejected[] = [
					'product_id' => $product_id,
					'name'       => $product->get_name(),
					'reason'     => 'Product is not purchasable (missing price or unsupported type).',
				];
				continue;
			}

			$resolved[] = [
				'id'       => $product_id,
				'quantity' => isset( $entry['quantity'] ) ? max( 1, (int) $entry['quantity'] ) : 1,
			];
		}

		if ( empty( $resolved ) ) {
			return MCPHelper::error(
				'no_valid_products',
				'None of the given products could be used. Resolve IDs with wpfunnels/search-products first.',
				[ 'rejected' => $rejected ]
			);
		}

		update_post_meta( $step_id, MCPHelper::productMetaKey( $step_type ), $resolved );

		$discount = isset( $input['discount_percentage'] ) ? (float) $input['discount_percentage'] : null;
		if ( null !== $discount && $discount > 0 ) {
			self::saveDiscount( $step_id, $step_type, $discount );
		}

		ContextTools::invalidateCache();

		return [
			'success'    => true,
			'step_id'    => $step_id,
			'step_type'  => $step_type,
			'products'   => self::attachedProducts( $step_id, $step_type ),
			'discount_percentage' => null !== $discount ? $discount : 0,
			'rejected'   => $rejected,
			'replaced'   => true,
		];
	}

	/**
	 * Products currently attached to a step, hydrated from WooCommerce.
	 *
	 * @param int    $step_id   Step id.
	 * @param string $step_type Step type.
	 * @return array
	 */
	public static function attachedProducts( $step_id, $step_type ) {
		$stored = get_post_meta( $step_id, MCPHelper::productMetaKey( $step_type ), true );
		if ( ! is_array( $stored ) || empty( $stored ) ) {
			return [];
		}

		$items = [];
		foreach ( $stored as $entry ) {
			$product_id = isset( $entry['id'] ) ? (int) $entry['id'] : 0;
			$product    = $product_id && function_exists( 'wc_get_product' ) ? wc_get_product( $product_id ) : null;

			$items[] = [
				'product_id' => $product_id,
				'quantity'   => isset( $entry['quantity'] ) ? (int) $entry['quantity'] : 1,
				'name'       => $product ? $product->get_name() : '(product no longer exists)',
				'price'      => $product ? (float) $product->get_price() : null,
				'exists'     => (bool) $product,
			];
		}

		return $items;
	}

	/**
	 * Persist the step-level discount in the meta shape each step type expects.
	 *
	 * WPFunnels stores one discount per step, in a different key and a different
	 * array shape per type — hence the switch rather than one generic write.
	 *
	 * @param int    $step_id   Step id.
	 * @param string $step_type Step type.
	 * @param float  $discount  Percentage off.
	 * @return void
	 */
	private static function saveDiscount( $step_id, $step_type, $discount ) {
		$discount = min( 100, max( 0, $discount ) );

		if ( 'checkout' === $step_type ) {
			update_post_meta(
				$step_id,
				'_wpfnl_checkout_discount_main_product',
				[
					'discountOptions'    => 'discount-percentage',
					'discountapplyto'    => 'regular',
					'mutedDiscountValue' => $discount,
				]
			);
			return;
		}

		update_post_meta(
			$step_id,
			'upsell' === $step_type ? '_wpfnl_upsell_discount' : '_wpfnl_downsell_discount',
			[
				'discountType'    => 'discount-percentage',
				'discountApplyTo' => 'regular',
				'discountValue'   => (string) $discount,
			]
		);
	}

	/**
	 * Format a product (or variation) for tool output.
	 *
	 * @param \WC_Product $product     Product object.
	 * @param string      $parent_name Parent name when formatting a variation.
	 * @return array
	 */
	private static function formatProduct( $product, $parent_name = '' ) {
		return [
			'product_id'     => $product->get_id(),
			'name'           => '' !== $parent_name
				? $parent_name . ' — ' . wp_strip_all_tags( $product->get_formatted_name() )
				: $product->get_name(),
			'type'           => $product->get_type(),
			'sku'            => $product->get_sku(),
			'price'          => '' !== $product->get_price() ? (float) $product->get_price() : null,
			'regular_price'  => '' !== $product->get_regular_price() ? (float) $product->get_regular_price() : null,
			'on_sale'        => $product->is_on_sale(),
			'stock_status'   => $product->get_stock_status(),
			'total_sales'    => (int) $product->get_total_sales(),
			'average_rating' => (float) $product->get_average_rating(),
			'purchasable'    => $product->is_purchasable(),
			'virtual'        => $product->is_virtual(),
			'downloadable'   => $product->is_downloadable(),
		];
	}
}
