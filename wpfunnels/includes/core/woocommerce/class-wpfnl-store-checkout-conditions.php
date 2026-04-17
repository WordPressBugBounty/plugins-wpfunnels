<?php
/**
 * Store Checkout Conditions Evaluator
 *
 * Evaluates which store checkout funnel should replace the default
 * WooCommerce checkout based on configurable conditions.
 *
 * Supported condition types:
 *   - all          : No condition — always matches (catch-all / default)
 *   - products     : Cart contains at least one of the specified products
 *   - categories   : Cart contains a product belonging to any of the specified categories
 *   - tags         : Cart contains a product with any of the specified tags
 *   - date_range   : Current date falls within the configured date range
 *
 * Priority rule: conditional funnels are always evaluated before
 * non-conditional (catch-all) ones.  Among funnels of the same kind
 * the most recently created funnel wins.
 *
 * Meta key stored on each store-checkout funnel's checkout step:
 *   _wpfnl_store_checkout_condition  (serialised / JSON array)
 *
 * @package WPFunnels\WooCommerce
 * @since   3.6.0
 */

namespace WPFunnels\WooCommerce;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WPFunnels\Wpfnl_functions;

/**
 * Class Wpfnl_Store_Checkout_Conditions
 *
 * @since 3.6.0
 */
class Wpfnl_Store_Checkout_Conditions {

	/**
	 * Meta key used to store the condition config on the checkout step.
	 */
	const META_KEY = '_wpfnl_store_checkout_condition';

	// -------------------------------------------------------------------------
	// Public API
	// -------------------------------------------------------------------------

	/**
	 * Find the checkout step ID for the best-matching store-checkout funnel.
	 *
	 * Priority order:
	 *  1. Funnels with specific conditions (newest first).
	 *  2. Funnels with no condition / catch-all (newest first).
	 *
	 * Only published funnels are considered.
	 *
	 * @return int|null  Step ID on match, null when no funnel matches.
	 * @since 3.6.0
	 */
	public static function get_matching_checkout_step_id() {
		$funnels = self::get_all_store_checkout_funnels();

		$conditional_matches = array();
		$catchall_match      = null;

		foreach ( $funnels as $funnel ) {
			if ( 'publish' !== $funnel->post_status ) {
				continue;
			}

			$step_id = self::get_checkout_step_id_for_funnel( $funnel->ID );
			if ( ! $step_id ) {
				continue;
			}

			$condition = self::get_condition( $step_id );

			if ( self::is_catch_all( $condition ) ) {
				// Catch-all: remember only the latest one (funnels are newest-first).
				if ( null === $catchall_match ) {
					$catchall_match = $step_id;
				}
				continue;
			}

			if ( self::evaluate( $condition ) ) {
				// Conditional match — newest first, so first hit wins.
				return $step_id;
			}
		}

		// No conditional funnel matched; fall back to the catch-all if available.
		return $catchall_match;
	}

	/**
	 * Return all published/draft store-checkout funnels ordered newest-first.
	 *
	 * @return \WP_Post[]
	 * @since 3.6.0
	 */
	public static function get_all_store_checkout_funnels() {
		return get_posts( array(
			'post_type'      => WPFNL_FUNNELS_POST_TYPE,
			'posts_per_page' => -1,
			'post_status'    => array( 'publish', 'draft' ),
			'orderby'        => 'date',
			'order'          => 'DESC',
			'meta_query'     => array(
				array(
					'key'   => '_wpfnl_funnel_type',
					'value' => 'store_checkout',
				),
			),
		) );
	}

	/**
	 * Retrieve the checkout step ID for a given store-checkout funnel.
	 *
	 * @param  int $funnel_id
	 * @return int|null
	 * @since  3.6.0
	 */
	public static function get_checkout_step_id_for_funnel( $funnel_id ) {
		$steps = Wpfnl_functions::get_steps( $funnel_id );
		foreach ( $steps as $step ) {
			$step_id = is_array( $step ) && isset( $step['id'] ) ? (int) $step['id'] : 0;
			if ( ! $step_id ) {
				continue;
			}
			if ( 'checkout' === get_post_meta( $step_id, '_step_type', true ) ) {
				$post = get_post( $step_id );
				if ( $post && in_array( $post->post_status, array( 'publish', 'draft' ), true ) ) {
					return $step_id;
				}
			}
		}
		return null;
	}

	/**
	 * Check whether a condition config is effectively a catch-all (no real condition).
	 *
	 * @param  array $condition
	 * @return bool
	 * @since  3.6.0
	 */
	public static function is_catch_all( array $condition ) {
		$type = isset( $condition['condition_type'] ) ? $condition['condition_type'] : 'all';

		if ( 'all' === $type ) {
			return true;
		}

		if ( 'rules' === $type ) {
			$rules = isset( $condition['rules'] ) && is_array( $condition['rules'] ) ? $condition['rules'] : array();
			$has_date = ! empty( $condition['use_date_range'] ) && ( ! empty( $condition['date_from'] ) || ! empty( $condition['date_to'] ) );
			return empty( $rules ) && ! $has_date;
		}

		return false;
	}

	/**
	 * Get the condition configuration stored on a checkout step.
	 *
	 * @param  int $step_id
	 * @return array  Defaults to ['condition_type' => 'all'] when none is stored.
	 * @since  3.6.0
	 */
	public static function get_condition( $step_id ) {
		$raw = get_post_meta( $step_id, self::META_KEY, true );

		if ( empty( $raw ) ) {
			return array( 'condition_type' => 'all' );
		}

		if ( is_string( $raw ) ) {
			$decoded = json_decode( $raw, true );
			if ( is_array( $decoded ) ) {
				return $decoded;
			}
		}

		if ( is_array( $raw ) ) {
			return $raw;
		}

		return array( 'condition_type' => 'all' );
	}

	/**
	 * Persist a condition configuration to a checkout step.
	 *
	 * @param  int   $step_id
	 * @param  array $condition
	 * @return bool
	 * @since  3.6.0
	 */
	public static function save_condition( $step_id, array $condition ) {
		$condition = self::sanitize_condition( $condition );
		$encoded   = wp_json_encode( $condition );
		$result    = update_post_meta( $step_id, self::META_KEY, $encoded );
		// update_post_meta returns false when the stored value is already identical.
		// That is not an error — treat it as success.
		if ( false === $result ) {
			return get_post_meta( $step_id, self::META_KEY, true ) === $encoded;
		}
		return true;
	}

	/**
	 * Sanitize and validate a condition array coming from user input.
	 *
	 * @param  array $condition
	 * @return array
	 * @since  3.6.0
	 */
	public static function sanitize_condition( array $condition ) {
		$type = isset( $condition['condition_type'] ) ? sanitize_key( $condition['condition_type'] ) : 'all';

		$allowed_types = array( 'all', 'rules', 'products', 'categories', 'tags', 'date_range' );
		if ( ! in_array( $type, $allowed_types, true ) ) {
			$type = 'all';
		}

		// New multi-rule format.
		if ( 'rules' === $type ) {
			$sanitized = array(
				'condition_type' => 'rules',
				'rules'          => array(),
				'date_from'      => isset( $condition['date_from'] ) ? sanitize_text_field( $condition['date_from'] ) : '',
				'date_to'        => isset( $condition['date_to'] )   ? sanitize_text_field( $condition['date_to'] )   : '',
			);

			$raw_rules = isset( $condition['rules'] ) && is_array( $condition['rules'] ) ? $condition['rules'] : array();
			foreach ( $raw_rules as $rule ) {
				if ( ! is_array( $rule ) ) {
					continue;
				}
				$rule_type = isset( $rule['type'] ) ? sanitize_key( $rule['type'] ) : '';
				if ( ! in_array( $rule_type, array( 'products', 'categories', 'tags' ), true ) ) {
					continue;
				}
				$values       = isset( $rule['values'] ) ? self::sanitize_int_array( (array) $rule['values'] ) : array();
				$value_labels = isset( $rule['value_labels'] ) ? self::sanitize_value_labels( (array) $rule['value_labels'] ) : array();
				$sanitized['rules'][] = array(
					'type'         => $rule_type,
					'values'       => $values,
					'value_labels' => $value_labels,
				);
			}

			return $sanitized;
		}

		// Legacy single-condition formats (preserved for backward compatibility).
		$sanitized = array( 'condition_type' => $type );

		switch ( $type ) {
			case 'products':
				$sanitized['products'] = self::sanitize_int_array(
					isset( $condition['products'] ) ? $condition['products'] : array()
				);
				// Also capture value_labels if provided.
				if ( isset( $condition['value_labels'] ) ) {
					$sanitized['value_labels'] = self::sanitize_value_labels( (array) $condition['value_labels'] );
				}
				break;

			case 'categories':
				$sanitized['categories'] = self::sanitize_int_array(
					isset( $condition['categories'] ) ? $condition['categories'] : array()
				);
				if ( isset( $condition['value_labels'] ) ) {
					$sanitized['value_labels'] = self::sanitize_value_labels( (array) $condition['value_labels'] );
				}
				break;

			case 'tags':
				$sanitized['tags'] = self::sanitize_int_array(
					isset( $condition['tags'] ) ? $condition['tags'] : array()
				);
				if ( isset( $condition['value_labels'] ) ) {
					$sanitized['value_labels'] = self::sanitize_value_labels( (array) $condition['value_labels'] );
				}
				break;

			case 'date_range':
				$sanitized['date_from'] = isset( $condition['date_from'] )
					? sanitize_text_field( $condition['date_from'] ) : '';
				$sanitized['date_to']   = isset( $condition['date_to'] )
					? sanitize_text_field( $condition['date_to'] ) : '';
				break;
		}

		return $sanitized;
	}

	/**
	 * Sanitize an array of {id, name} label objects.
	 *
	 * @param  array $labels
	 * @return array
	 * @since  3.6.0
	 */
	private static function sanitize_value_labels( array $labels ) {
		$sanitized = array();
		foreach ( $labels as $label ) {
			if ( ! is_array( $label ) ) {
				continue;
			}
			$id   = isset( $label['id'] )   ? absint( $label['id'] )                       : 0;
			$name = isset( $label['name'] ) ? sanitize_text_field( $label['name'] ) : '';
			if ( $id ) {
				$sanitized[] = array( 'id' => $id, 'name' => $name );
			}
		}
		return $sanitized;
	}

	// -------------------------------------------------------------------------
	// Condition Evaluation
	// -------------------------------------------------------------------------

	/**
	 * Evaluate whether the given condition matches the current request/cart.
	 *
	 * @param  array $condition
	 * @return bool
	 * @since  3.6.0
	 */
	public static function evaluate( array $condition ) {
		$type = isset( $condition['condition_type'] ) ? $condition['condition_type'] : 'all';

		switch ( $type ) {
			case 'all':
				return true;

			case 'rules':
				return self::evaluate_rules( $condition );

			case 'products':
				return self::evaluate_products( $condition );

			case 'categories':
				return self::evaluate_categories( $condition );

			case 'tags':
				return self::evaluate_tags( $condition );

			case 'date_range':
				return self::evaluate_date_range( $condition );

			default:
				return false;
		}
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Evaluate multiple rules (OR logic) with an optional AND date range.
	 *
	 * @param  array $condition
	 * @return bool
	 * @since  3.6.0
	 */
	private static function evaluate_rules( array $condition ) {
		$rules = isset( $condition['rules'] ) ? (array) $condition['rules'] : array();

		if ( empty( $rules ) ) {
			// No rules configured — treat as "all".
			return true;
		}

		$rule_matched = false;
		foreach ( $rules as $rule ) {
			if ( ! is_array( $rule ) ) {
				continue;
			}
			$rule_type = isset( $rule['type'] ) ? $rule['type'] : '';
			$values    = isset( $rule['values'] ) ? array_map( 'intval', (array) $rule['values'] ) : array();

			switch ( $rule_type ) {
				case 'products':
					if ( self::evaluate_products_by_ids( $values ) ) {
						$rule_matched = true;
						break 2;
					}
					break;

				case 'categories':
					if ( self::evaluate_categories_by_ids( $values ) ) {
						$rule_matched = true;
						break 2;
					}
					break;

				case 'tags':
					if ( self::evaluate_tags_by_ids( $values ) ) {
						$rule_matched = true;
						break 2;
					}
					break;
			}
		}

		if ( ! $rule_matched ) {
			return false;
		}

		// AND the date range when both are provided.
		$date_from = isset( $condition['date_from'] ) ? $condition['date_from'] : '';
		$date_to   = isset( $condition['date_to'] )   ? $condition['date_to']   : '';
		if ( $date_from || $date_to ) {
			return self::evaluate_date_range( array( 'date_from' => $date_from, 'date_to' => $date_to ) );
		}

		return true;
	}

	/**
	 * @param  array $condition
	 * @return bool
	 */
	private static function evaluate_products( array $condition ) {
		$ids = isset( $condition['products'] ) ? array_map( 'intval', (array) $condition['products'] ) : array();
		return self::evaluate_products_by_ids( $ids );
	}

	/**
	 * @param  array $condition
	 * @return bool
	 */
	private static function evaluate_categories( array $condition ) {
		$ids = isset( $condition['categories'] ) ? array_map( 'intval', (array) $condition['categories'] ) : array();
		return self::evaluate_categories_by_ids( $ids );
	}

	/**
	 * @param  array $condition
	 * @return bool
	 */
	private static function evaluate_tags( array $condition ) {
		$ids = isset( $condition['tags'] ) ? array_map( 'intval', (array) $condition['tags'] ) : array();
		return self::evaluate_tags_by_ids( $ids );
	}

	/**
	 * Check whether any cart item matches the given product IDs.
	 *
	 * @param  int[] $product_ids
	 * @return bool
	 */
	private static function evaluate_products_by_ids( array $product_ids ) {
		if ( empty( $product_ids ) ) {
			return false;
		}

		$cart = self::get_wc_cart();
		if ( ! $cart ) {
			return false;
		}

		foreach ( $cart->get_cart() as $item ) {
			$item_product_id   = (int) ( $item['product_id']   ?? 0 );
			$item_variation_id = (int) ( $item['variation_id'] ?? 0 );

			if ( in_array( $item_product_id, $product_ids, true ) ) {
				return true;
			}
			if ( $item_variation_id && in_array( $item_variation_id, $product_ids, true ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether any cart item belongs to one of the given category IDs.
	 *
	 * @param  int[] $category_ids
	 * @return bool
	 */
	private static function evaluate_categories_by_ids( array $category_ids ) {
		if ( empty( $category_ids ) ) {
			return false;
		}

		$cart = self::get_wc_cart();
		if ( ! $cart ) {
			return false;
		}

		foreach ( $cart->get_cart() as $item ) {
			$product_id = (int) ( $item['product_id'] ?? 0 );
			if ( ! $product_id ) {
				continue;
			}
			$product_cats = wp_get_post_terms( $product_id, 'product_cat', array( 'fields' => 'ids' ) );
			if ( is_array( $product_cats ) && ! empty( array_intersect( $product_cats, $category_ids ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check whether any cart item carries one of the given tag IDs.
	 *
	 * @param  int[] $tag_ids
	 * @return bool
	 */
	private static function evaluate_tags_by_ids( array $tag_ids ) {
		if ( empty( $tag_ids ) ) {
			return false;
		}

		$cart = self::get_wc_cart();
		if ( ! $cart ) {
			return false;
		}

		foreach ( $cart->get_cart() as $item ) {
			$product_id = (int) ( $item['product_id'] ?? 0 );
			if ( ! $product_id ) {
				continue;
			}
			$product_tags = wp_get_post_terms( $product_id, 'product_tag', array( 'fields' => 'ids' ) );
			if ( is_array( $product_tags ) && ! empty( array_intersect( $product_tags, $tag_ids ) ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param  array $condition
	 * @return bool
	 */
	private static function evaluate_date_range( array $condition ) {
		$date_from = isset( $condition['date_from'] ) ? sanitize_text_field( $condition['date_from'] ) : '';
		$date_to   = isset( $condition['date_to'] )   ? sanitize_text_field( $condition['date_to'] )   : '';

		$now = current_time( 'timestamp' );

		if ( $date_from ) {
			$from_ts = strtotime( $date_from );
			if ( false !== $from_ts && $now < $from_ts ) {
				return false;
			}
		}

		if ( $date_to ) {
			// Include the entire "to" day.
			$to_ts = strtotime( $date_to . ' 23:59:59' );
			if ( false !== $to_ts && $now > $to_ts ) {
				return false;
			}
		}

		// If both dates are empty treat as always-active.
		return true;
	}

	/**
	 * Get the WooCommerce cart instance safely.
	 *
	 * @return \WC_Cart|null
	 */
	private static function get_wc_cart() {
		if ( ! function_exists( 'WC' ) ) {
			return null;
		}
		$wc = WC();
		return ( $wc && $wc->cart instanceof \WC_Cart ) ? $wc->cart : null;
	}

	/**
	 * Sanitize an array of values to positive integers, filtering out zeroes.
	 *
	 * @param  mixed $input
	 * @return int[]
	 */
	private static function sanitize_int_array( $input ) {
		if ( ! is_array( $input ) ) {
			return array();
		}
		return array_values( array_filter( array_map( 'absint', $input ) ) );
	}
}
