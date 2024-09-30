<?php
/**
 * This class is responsible for all the helper functions
 *
 * @package WPFunnels
 */
namespace WPFunnels;

use ElementorPro\Modules\WpCli\Update;
use WPFunnels\Metas\Wpfnl_Step_Meta_keys;

class Wpfnl_functions {

	public static $installed_plugins;

	/**
	 * Get all the steps of the funnel
	 *
	 * @param $funnel_id
	 *
	 * @return array|mixed
	 */
	public static function get_steps($funnel_id) {
		if (!is_int($funnel_id) && !is_string($funnel_id)) {
			// Throw an error or handle this case as appropriate for your application
			throw new \InvalidArgumentException('Funnel ID must be an integer or a string');
		}

		$steps = get_post_meta($funnel_id, '_steps_order', true);
		if (!is_array($steps)) {
			$steps = array();
		}
		return $steps;
	}


	/**
	 * Check if the associated order is from a funnel.
	 *
	 * This function checks if the given order is associated with a funnel.
	 * It returns true if the order is from a funnel, and false otherwise.
	 * Previously 'get_funnel_id_from_order' function parameter was order id but from 2.8.0 version it changed to order object
	 *
	 * @param \WC_Order $order The WooCommerce order object.
	 * @return bool True if the order is from a funnel, false otherwise.
	 * @since 2.8.0
	 */
	public static function check_if_funnel_order( $order ) {
		if ( false === is_a( $order, 'WC_Order' ) ) {
			return false;
		}

		$funnel_id = self::get_funnel_id_from_order( $order );
		if ( ! $funnel_id ) {
			return false;
		}
		return true;
	}


	/**
	 * Get the associated funnel ID from the order ID.
	 *
	 * This function retrieves the ID of the associated funnel for a given order.
	 * Previously data was retrieved by post meta table but from 2.8.0 version user get_meta function to retrieve meta
	 *
	 * @param \WC_Order|Int $order The WooCommerce order object or Order ID of WooCommerce order.
	 * @return int|false The ID of the associated funnel, or false if not found.
	 * @since 2.8.0
	 */
	public static function get_funnel_id_from_order( $order ) {
		if ( ! $order ) {
			return false;
		}

		if ( false === is_a( $order, 'WC_Order' ) ) {
			$order_id = intval( $order );
			$order    = wc_get_order( $order_id );
			if ( false === is_a( $order, 'WC_Order' ) ) {
				return false;
			}
		}

		$funnel_id = $order->get_meta( '_wpfunnels_funnel_id' ) ? $order->get_meta( '_wpfunnels_funnel_id' ) : $order->get_meta( '_wpfunnels_parent_funnel_id' );
		if ( ! $funnel_id ) {
			return false;
		}
		return intval( $funnel_id );
	}

	/**
	 * Check if the given string is a date or not
	 *
	 * @param $date
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function validate_date( $date ) {
		return (bool) strtotime( $date );
	}

	/**
	 * Define constant if it is not set yet
	 *
	 * @param $name
	 * @param $value
	 *
	 * @since 2.0.3
	 */
	public static function maybe_define_constant( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
	}

	/**
	 * Set do not cache constants
	 */
	public static function do_not_cache() {
		global $post;

		if ( ! apply_filters( 'wpfunnels/do_not_cache', true, $post->ID ) ) {
			return;
		}
		self::maybe_define_constant( 'DONOTCACHEPAGE', true );
		self::maybe_define_constant( 'DONOTCACHEOBJECT', true );
		self::maybe_define_constant( 'DONOTCACHEDB', true );
		nocache_headers();
	}

	/**
	 * Return formatted date from the
	 * given date object
	 *
	 * @param $date
	 *
	 * @return false|string
	 * @since  1.0.0
	 */
	public static function get_formatted_date( $date ) {
		return date( 'Y-m-d h:i A', strtotime( $date ) );
	}


	/**
	 * Get checkout ID
	 *
	 * @return bool|int
	 */
	public static function get_checkout_id_from_post_data() {
		$checkout_id = ! empty( $_POST['_wpfunnels_checkout_id'] ) ? filter_var( wp_unslash( $_POST['_wpfunnels_checkout_id'] ), FILTER_SANITIZE_NUMBER_INT ) : false;
		return $checkout_id ? intval( $checkout_id ) : false;
	}


	/**
	 * Get checkout id from post
	 *
	 * @return bool|int
	 */
	public static function get_checkout_id_from_post( $post = null ) {
		if ( isset( $post['post_data'] ) ) {
			$post_data = array();
			parse_str( $post['post_data'], $post_data );
			if ( isset( $post_data['_wpfunnels_checkout_id'] ) ) {
				return $post_data['_wpfunnels_checkout_id'];
			}
		}
		return false;
	}


	/**
	 * Check order bump clicked or not from post data
	 *
	 * @return bool True|False
	 * @since 2.8.0
	 */
	public static function is_orderbump_clicked_from_post_data() {
		if ( ! empty( $_POST['post_data'] ) ) {
			wp_parse_str( $_POST['post_data'], $post_data );
			return ! empty( $post_data['_wpfunnels_order_bump_clicked'] ) && 'yes' === $post_data['_wpfunnels_order_bump_clicked'];
		}
		return ! empty( $_POST['_wpfunnels_order_bump_clicked'] ) && 'yes' === $_POST['_wpfunnels_order_bump_clicked'];
	}


	/**
	 * Check checkoutify order bump clicked or not from post data
	 *
	 * @return bool True|False
	 * @since 2.8.0
	 */
	public static function is_checkoutify_orderbump_clicked_from_post_data() {
		if ( ! empty( $_POST['post_data'] ) ) {
			wp_parse_str( $_POST['post_data'], $post_data );
			return ! empty( $post_data['checkoutify_orderbump_clicked'] ) && 'yes' === $post_data['checkoutify_orderbump_clicked'];
		}
		return ! empty( $_POST['checkoutify_orderbump_clicked'] ) && 'yes' === $_POST['checkoutify_orderbump_clicked'];
	}


	/**
	 * Check qunatity select or not from post data
	 *
	 * @return bool True|False
	 * @since 2.8.0
	 */
	public static function maybe_select_quantity_from_post_data() {
		if ( ! empty( $_POST['post_data'] ) ) {
			$post_data = array();
			wp_parse_str( $_POST['post_data'], $post_data );
			return ! empty( $post_data['_wpfunnels_select_quantity'] ) && 'yes' === $post_data['_wpfunnels_select_quantity'];
		}
		return ! empty( $_POST['_wpfunnels_select_quantity'] ) && 'yes' === $_POST['_wpfunnels_select_quantity'];
	}



	/**
	 * Check variation is selected or not from post data
	 *
	 * @return bool True|False
	 * @since 2.8.0
	 */
	public static function is_variation_selected_from_post_data() {
		if ( ! empty( $_POST['post_data'] ) ) {
			wp_parse_str( $_POST['post_data'], $post_data );
			return ! empty( $post_data['_wpfunnels_variable_product'] ) && 'selected' === $post_data['_wpfunnels_variable_product'];
		}
		return ! empty( $_POST['_wpfunnels_variable_product'] ) && 'selected' === $_POST['_wpfunnels_variable_product'];
	}


	/**
	 * Check variation is selected or not from post data
	 *
	 * @return bool True|False
	 * @since 2.8.0
	 */
	public static function is_coupon_applied_from_post_data() {
		if ( ! empty( $_POST['post_data'] ) ) {
			wp_parse_str( $_POST['post_data'], $post_data );
			return ! empty( $post_data['_wpfunnels_auto_coupon'] ) && 'yes' === $post_data['_wpfunnels_auto_coupon'];
		}
		return ! empty( $_POST['_wpfunnels_auto_coupon'] ) && 'yes' === $_POST['_wpfunnels_auto_coupon'];
	}


	/**
	 * Get funnel id
	 *
	 * @param $step_id
	 *
	 * @return mixed
	 */
	public static function get_funnel_id_from_step( $step_id ) {
		$funnel_id = get_post_meta( $step_id, '_funnel_id', true );
		return intval( $funnel_id );
	}

	/**
	 * Get order id from funnel post data
	 *
	 * @return false|int
	 * @since 2.8.2
	 */
	public static function get_order_id_from_post_data() {
		if ( ! empty( $_POST['_wpfunnels_order_unique_identifier'] ) ) {
			global $wpdb;
			$identifier    = wp_unslash( $_POST['_wpfunnels_order_unique_identifier'] );
			$prepare_guery = $wpdb->prepare( "SELECT `post_id` FROM {$wpdb->postmeta} WHERE meta_key ='_wpfunnels_order_unique_identifier' and meta_value like '%s'", $identifier );
			$get_value     = $wpdb->get_row( $prepare_guery );
			return $get_value ? intval( $get_value->post_id ) : false;
		}
		return false;
	}


	/**
	 * Get checkout id from order
	 *
	 * @param $order_id
	 *
	 * @return int
	 */
	public static function get_checkout_id_from_order( $order_id ) {
		if ( !self::is_wc_active() ) {
			return false;
		}

		$order = wc_get_order( $order_id );

		if ( false === is_a( $order, 'WC_Order' ) ) {
			return false;
		}

		$checkout_id = $order->get_meta( '_wpfunnels_checkout_id' );
		return $checkout_id ? intval( $checkout_id ) : false;
	}


	/**
	 * Get funnel from post data
	 *
	 * @return bool|int
	 */
	public static function get_funnel_id_from_post_data() {
		if ( ! empty( $_POST['_wpfunnels_funnel_id'] ) ) {
			$funnel_id = filter_var( wp_unslash( $_POST['_wpfunnels_funnel_id'] ), FILTER_SANITIZE_NUMBER_INT );
			return $funnel_id ? intval( $funnel_id ) : false;
		}
		return false;
	}


	/**
	 * Get funnel id from step page
	 *
	 * @return false|mixed
	 */
	public static function get_funnel_id() {
		global $post;
		if ( $post ) {
			$funnel_id = get_post_meta( $post->ID, '_funnel_id', true );
			return $funnel_id ? intval( $funnel_id ) : false;
		}
		return false;
	}

	/**
	 * Unserialize data
	 *
	 * @param $data
	 *
	 * @return mixed
	 * @since  1.0.0
	 */
	public static function unserialize_array_data( $data ) {
		return maybe_unserialize( $data );
	}


	/**
	 * Get formatted string with phrase
	 * e.g: 1 item if singular or
	 * 2 items if plural
	 *
	 * @param Int    $number
	 * @param String $singular
	 * @param String $plural
	 *
	 * @return String
	 * @since  1.0.0
	 */
	public static function get_formatted_data_with_phrase( $number, $singular = '', $plural = 's' ) {
		return ( is_numeric( $number ) && 0 <= $number ) ? ( 1 < $number ? $plural : $singular ) : null;
	}


	/**
	 * Get formatted string from funnel status
	 *
	 * @param $status
	 *
	 * @return string
	 * @since  1.0.0
	 */
	public static function get_formatted_status( $status ) {
		switch ( $status ) {
			case 'publish':
				return 'Published';
			case 'draft':
				return 'Draft';
			default:
				return $status;
		}
	}


	/**
	 * Generate active class for funnel menus
	 *
	 * @param $key
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function define_active_class($key)
	{
		$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_SPECIAL_CHARS);
		if ($page === WPFNL_FUNNEL_PAGE_SLUG && $key === 'overview') {
			return true;
		}

		if ( $page === 'create_funnel' && $key === 'create_funnel' ) {
			return true;
		}

		if ( $page === 'wpfnl_settings' && $key === 'settings' ) {
			return true;
		}

		if ( $page === 'wpfunnels_integrations' && $key === 'integrations' ) {
			return true;
		}
	}


	/**
	 * Check If module exists or not
	 *
	 * @param $id
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function check_if_module_exists( $id ) {
		return 'publish' === get_post_status( $id ) || 'draft' === get_post_status( $id ) || 'trash' === get_post_status( $id );
	}


	/**
	 * Check if the cpt is step or not
	 *
	 * @param $step_id
	 * @param string  $type
	 *
	 * @return bool
	 */
	public static function check_if_this_is_step_type_by_id( $step_id, $type = 'landing' ) {
		$post_type = get_post_type( $step_id );
		if ( WPFNL_STEPS_POST_TYPE === $post_type ) {
			if ( $type === get_post_meta( $step_id, '_step_type', true ) ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Get thankyou page id
	 *
	 * @param $funnel_id
	 *
	 * @return bool|mixed
	 *
	 * @since 2.2.6
	 */
	public static function get_thankyou_page_id( $funnel_id ) {
		$steps = self::get_steps( $funnel_id );
		if ( is_array( $steps ) ) {
			foreach ( $steps as $step ) {
				if ( 'thankyou' === $step['step_type'] ) {
					return $step['id'];
				}
			}
		}
		return false;
	}


	/**
	 * Check if the current post type is step or not
	 *
	 * @return bool
	 * @since  2.0.3
	 */
	public static function is_funnel_step_page( $post_type = '' ) {
		if ( self::get_current_post_type( $post_type ) === WPFNL_STEPS_POST_TYPE ) {
			return true;
		}

		$post_id = null;
		if ( wp_doing_ajax() || isset( $_GET['wc-ajax'] ) ) {
			$post_id = self::get_checkout_id_from_post( $_POST );
		}
		global $post;

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}
		$post = $post_id ? get_post( $post_id ) : $post;

		$post_type = $post->post_type ?? '';
		if ( self::get_current_post_type( $post_type ) === WPFNL_STEPS_POST_TYPE ) {
			return true;
		}

		return false;
	}


	/**
	 * Get current post type
	 *
	 * @param $post_type
	 *
	 * @return string
	 *
	 * @since 2.3.5
	 */
	public static function get_current_post_type( $post_type ) {
		global $post;
		if ( '' === $post_type && is_object( $post ) ) {
			$post_type = $post->post_type;
		} else {
			$post_id = '';
			if ( wp_doing_ajax() || isset( $_GET['wc-ajax'] ) ) {
				$post_id = self::get_checkout_id_from_post( $_POST );
			}

			if ( $post_id ) {
				$post      = get_post( $post_id );
				$post_type = $post->post_type;
			}
		}
		return $post_type;
	}


	/**
	 * Check if funnel checkout page
	 *
	 * @param $funnel_id
	 *
	 * @return bool
	 */
	public static function is_funnel_checkout_page() {
		if ( isset( $_POST['post_data'] ) ) {
			parse_str( $_POST['post_data'], $post_data );
			if ( isset( $post_data['_wpfunnels_checkout_id'] ) ) {
				return array(
					'status' => true,
					'id'     => $post_data['_wpfunnels_checkout_id'],
				);
			}
		}
		return array(
			'status' => false,
			'id'     => '',
		);
	}


	/**
	 * Check if funnel exists
	 *
	 * @param $funnel_id
	 *
	 * @return bool
	 */
	public static function is_funnel_exists( $funnel_id ) {
		if (!is_int($funnel_id) && !is_string($funnel_id)) {
			// Throw an error or handle this case as appropriate for your application
			throw new \InvalidArgumentException('Funnel ID must be an integer or a string');
		}

		if ( ! $funnel_id ) {
			return false;
		}

		if ( false === get_post_status( $funnel_id ) ) {
			return true;
		}
		return false;
	}


	/**
	 * Function to check if the current page is a post edit page
	 *
	 * @return bool
	 *
	 * @since 2.0.3
	 */
	public static function is_step_edit_page() {

		$step_id = -1;
		if ( is_admin() && isset( $_REQUEST['action'] ) ) {
			if ( 'edit' === $_REQUEST['action'] && isset( $_GET['post'] ) ) {
				$step_id = isset( $_GET['post'] ) ? $_GET['post'] : - 1;
			} 			elseif ( isset( $_REQUEST['wpfunnels_gb'] ) && isset( $_POST['post_id'] ) ){ //phpcs:ignore
				$step_id = intval( $_POST['post_id'] ); //phpcs:ignore
			}
			if ( $step_id === - 1 ) {

				return false;
			}
			$get_post_type = get_post_type( $step_id );
			if ( WPFNL_STEPS_POST_TYPE === $get_post_type ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Check if the cpt is step or not
	 *
	 * @param string $type
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function check_if_this_is_step_type( $type = 'landing' ) {
		$post_type = get_post_type();

		if ( WPFNL_STEPS_POST_TYPE === $post_type ) {
			global $post;
			if ( $type === get_post_meta( $post->ID, '_step_type', true ) ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Hooks for start and end the journey
	 * of a funnel
	 */
	public static function start_journey() {
		$post_type = get_post_type();
		if ( WPFNL_STEPS_POST_TYPE === $post_type ) {
			global $post;
			$step_id     = $post->ID;
			$funnel_id   = self::get_funnel_id_from_step( $step_id );
			$steps       = self::get_steps( $funnel_id );
			$funnel_data = self::get_funnel_data( $funnel_id );
			// start the journey
			if ( $steps && is_array( $steps ) ) {
				if ( $steps[0]['id'] === $step_id ) {
					do_action( 'wpfunnels/funnel_journey_starts', $step_id, $funnel_id );
				}
			}

			// end the journey
			if ( $funnel_data && isset( $funnel_data['drawflow']['Home']['data'] ) ) {
				$funnel_data = $funnel_data['drawflow']['Home']['data'];
				foreach ( $funnel_data as $data ) {
					$info      = $data['data'];
					$step_type = $info['step_type'];

					if ( 'conditional' !== $step_type ) {
						$_step_id   = isset( $info['step_id'] ) ? $info['step_id'] : '';
						$output_con = $data['outputs'];

						if ( empty( $output_con ) ) {
							if ( $_step_id === $step_id ) {
								do_action( 'wpfunnels/funnel_journey_end', $step_id, $funnel_id );
								delete_option( 'optin_data' );
								break;
							}
						}
					}
				}
			}
		}
	}


	/**
	 * Conditional node logic check
	 *
	 * @param $funnel_id
	 * @param $order
	 * @param $condition_identifier
	 * @param $current_page_id
	 * @param $checker
	 *
	 * @return bool
	 *
	 * @since 2.0.2
	 */
	public static function check_condition( $funnel_id, $order, $condition_identifier, $current_page_id, $checker = 'accept' ) {
		$group_conditions = get_post_meta( $funnel_id, $condition_identifier, true );

		if ( $group_conditions ) {
			// Loop through group condition.
			foreach ( $group_conditions as $group ) {
				if ( empty( $group ) ) {
					continue;
				}

				$match_group = true;
				// Loop over rules and determine if all rules match.
				foreach ( $group as $rule ) {
					if ( ! self::match_rule( $funnel_id, $rule, $order, $current_page_id, $checker ) ) {
						$match_group = false;
						break;
					}
				}

				// If this group matches, show the field group.
				if ( $match_group ) {
					return true;
				} else {
					return false;
				}
			}
		}
		if ( $checker == 'accept' ) {
			return true;
		}
		// Return default.
		return false;
	}


	/**
	 * Check if rule is matched
	 *
	 * @param $rule
	 * @param $order
	 * @param $current_page_id
	 * @param $checker
	 *
	 * @return mixed
	 *
	 * @since 2.0.2
	 */
	public static function match_rule( $funnel_id, $rule, $order, $current_page_id, $checker ) {
		if ( isset( $rule['field'] ) && $rule['field'] == 'downsell' ) {
			$rule['field'] = 'upsell';
		}
		$condition = $rule['field'];
		if ( strpos( $rule['field'], 'optin' ) ) {
			$condition = 'optin';
		}
		$checker_function = $condition . '_condition_checker';
		return self::$checker_function( $funnel_id, $rule, $order, $current_page_id, $checker );
	}


	/**
	 * Upsell condition checker for conditional step
	 *
	 * @param Integer $funnel_id
	 * @param Array   $data
	 * @param Object  $order
	 * @param Integer $current_page_id
	 * @param String  $checker
	 *
	 * @return Boolean
	 *
	 * @since 2.5.7
	 */
	public static function upsell_condition_checker( $funnel_id, $data, $order, $current_page_id, $checker ) {

		if ( $data['value'] == 'yes' ) {
			if ( $checker == 'accept' ) {
				return true;
			} else {
				return false;
			}
		} elseif ( $data['value'] == 'no' ) {

			if ( $checker == 'reject' ) {
				return true;
			} else {
				return false;
			}
		}
		return false;
	}


	/**
	 * Optin condition checker for conditional step
	 *
	 * @param Integer $funnel_id
	 * @param Array   $data
	 * @param Object  $order
	 * @param Integer $current_page_id
	 * @param String  $checker
	 *
	 * @return Boolean
	 *
	 * @since 2.5.7
	 */
	public function optin_condition_checker( $funnel_id, $rule, $data, $current_page_id, $checker = 'accept' ) {

		if ( isset( $data['step_id'] ) ) {
			$step_id     = str_replace( 'optin_', '', $data['step_id'] );
			$optin_steps = get_option( 'optin_data' );
			if ( isset( $optin_steps['optin_data'] ) ) {
				$key = array_search( $step_id, array_column( $optin_steps['optin_data'], 'step_id' ) );
				if ( false !== $key ) {
					unset( $optin_steps[ $key ] );
					update_option( 'optin_data', $optin_steps );
					return true;
				}
			}
		} elseif ( ! empty( $data['cta'] ) ) {
			return false;
		}

		if ( isset( $rule['field'] ) ) {
			$step_id     = str_replace( 'optin_', '', $rule['field'] );
			$optin_steps = get_option( 'optin_data' );
			if ( isset( $optin_steps['optin_data'] ) ) {
				$key = array_search( $step_id, array_column( $optin_steps['optin_data'], 'step_id' ) );
				if ( false !== $key ) {
					unset( $optin_steps[ $key ] );
					update_option( 'optin_data', $optin_steps );
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Order bump condition checker
	 *
	 * @param $data
	 * @param $order
	 * @param $current_page_id
	 *
	 * @return bool
	 */
	public static function orderbump_condition_checker( $funnel_id, $data, $order, $current_page_id, $checker = 'accept' ) {
		$order_bump_accepted = '';
		if ( self::is_wc_active() ) {
			$order_bump_accepted = WC()->session->get( 'order_bump_accepted' );
		}
		return $data['value'] == $order_bump_accepted;
	}


	/**
	 * Check cart total condition in conditional node
	 *
	 * @param $data
	 * @param $order
	 * @param $current_page_id
	 *
	 * @return bool
	 */
	public static function carttotal_condition_checker( $funnel_id, $data, $order, $current_page_id, $checker = 'accept' ) {
		$cart_total = $order->get_total();

		$checker = false;
		if ( $data['condition'] == 'greater' ) {
			if ( $cart_total > $data['value'] ) {
				$checker = true;
			}
		} elseif ( $data['condition'] == 'equal' ) {
			if ( $cart_total == $data['value'] ) {
				$checker = true;
			}
		} elseif ( $data['condition'] == 'less' ) {
			if ( $cart_total < $data['value'] ) {
				$checker = true;
			}
		}
		return $checker;
	}


	/**
	 * Billing country condition checker
	 *
	 * @param Array
	 * @param Object
	 * @param String
	 * @param String
	 *
	 * @return Bool
	 * @since  2.4.18
	 */
	public static function billing_condition_checker( $funnel_id, $data, $order, $current_page_id, $checker ) {
		if ( $order ) {
			$billing_country = $order->get_billing_country();
			if ( isset( $data['field'] ) && 'billing' === $data['field'] && isset( $data['value'] ) && $billing_country === $data['value'] ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Shipping country condition checker
	 *
	 * @param Array
	 * @param Object
	 * @param String
	 * @param String
	 *
	 * @return Bool
	 * @since  2.4.18
	 */
	public static function shipping_condition_checker( $funnel_id, $data, $order, $current_page_id, $checker ) {
		if ( $order ) {
			$shipping_country = $order->get_shipping_country();
			if ( isset( $data['field'] ) && 'shipping' === $data['field'] && isset( $data['value'] ) && $shipping_country === isset( $data['value'] ) ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Get next step of the
	 * funnel
	 *
	 * @param $funnel_id
	 * @param $step_id
	 * @param bool      $condition
	 *
	 * @return Mix
	 *
	 * @since 1.0.0
	 */
	public static function get_next_step( $funnel_id, $step_id, $condition = true ) {
		if ( ! $funnel_id || ! $step_id ) {
			return false;
		}
		$parent_step_id = get_post_meta( $step_id, '_parent_step_id', true );
		if ( $parent_step_id ) {
			$step_id = $parent_step_id;
		}

		$funnel_data = self::get_funnel_data( $funnel_id );

		if ( ! $funnel_data || ! isset( $funnel_data['drawflow']['Home']['data'] ) ) {
			return false;
		}

		$node_id   = self::get_node_id( $funnel_id, $step_id );
		$node_data = $funnel_data['drawflow']['Home']['data'];

		if ( ! is_array( $node_data ) || empty( $node_data ) ) {
			return false;
		}

		$next_node_id = '';

		foreach ( $node_data as $node_key => $node_value ) {
			if ( $node_value['id'] != $node_id ) {
				continue;
			}

			if ( $condition ) {
				$next_node_id = isset( $node_value['outputs']['output_1']['connections'][0]['node'] ) ? $node_value['outputs']['output_1']['connections'][0]['node'] : '';
			} else {
				$next_node_id = isset( $node_value['outputs']['output_2']['connections'][0]['node'] ) ? $node_value['outputs']['output_2']['connections'][0]['node'] : '';
			}

			if ( ! $next_node_id ) {
				self::redirect_to_deafult_thankyou();
			}

			$next_step_id   = self::get_step_by_node( $funnel_id, $next_node_id );
			$next_step_type = self::get_node_type( $node_data, $next_node_id );

			return apply_filters(
				'wpfunnels/next_step_data',
				array(
					'step_id'   => $next_step_id,
					'step_type' => $next_step_type,
				)
			);

		}

		return false;
	}


	/**
	 * Redirect deafult thank you page
	 */
	public static function redirect_to_deafult_thankyou() {

		if ( isset( $_POST['order_id'] ) && $_POST['order_id'] ) {
			$url = home_url() . '/checkout/order-received/' . $_POST['order_id'] . '/?key=' . $_POST['order_key'];
			return $url;
		} else {
			add_action(
				'template_redirect',
				function( $order_id ) {
					if ( isset( $_GET['order-received'] ) && $_GET['order-received'] ) {
						$url = '';
						$url = add_query_arg( 'id', $_GET['order-received'], $url );
						wp_safe_redirect( wp_sanitize_redirect( esc_url_raw( $url ) ) );
						exit;
					} elseif ( isset( $_POST['order_id'] ) && $_POST['order_id'] ) {
						$url = '';
						$url = add_query_arg( 'id', $_POST['order_id'], $url );
						wp_safe_redirect( wp_sanitize_redirect( esc_url_raw( $url ) ) );
						exit;
					}
				}
			);
		}
	}


	/**
	 * Get previous step of the
	 * funnel
	 *
	 * @param $funnel_id
	 * @param $step_id
	 * @param bool      $condition
	 *
	 * @return Mix
	 *
	 * @since 1.0.0
	 */
	public static function get_prev_step( $funnel_id, $step_id, $condition = true ) {
		if ( $funnel_id && ! $step_id ) {
			return false;
		}
		$funnel_data = self::get_funnel_data( $funnel_id );
		if ( $funnel_data ) {
			$node_id   = self::get_node_id( $funnel_id, $step_id );
			$node_data = $funnel_data['drawflow']['Home']['data'];

			foreach ( $node_data as $node_key => $node_value ) {
				if ( $node_value['id'] == $node_id ) {
					if ( $condition ) {
						if ( ! empty( $node_value['inputs'] ) ) {
							$prev_node_id = $node_value['inputs']['input_1']['connections'][0]['node'];
						} else {
							$prev_node_id = '';
						}
					} else {
						if ( ! empty( $node_value['inputs'] ) ) {
							$prev_node_id = $node_value['inputs']['input_2']['connections'][0]['node'];
						} else {
							$prev_node_id = '';
						}
					}
					$prev_step_id   = self::get_step_by_node( $funnel_id, $prev_node_id );
					$prev_step_type = self::get_node_type( $node_data, $prev_node_id );
					if ( $prev_step_type == 'conditional' ) {
						return self::get_prev_step( $funnel_id, $prev_step_id );

					} else {

						return array(
							'step_id'   => $prev_step_id,
							'step_type' => $prev_step_type,
						);
					}
				}
			}
		}
		return false;
	}

	/**
	 * Get node type
	 */
	public static function get_node_type( $node_data, $node_id ) {
		foreach ( $node_data as $node_key => $node_value ) {
			if ( $node_value['id'] == $node_id ) {
				return $node_value['data']['step_type'];
			}
		}
	}

	/**
	 * Get step by node
	 */
	public static function get_step_by_node( $funnel_id, $node_id ) {
		if ( ! $funnel_id && ! $node_id ) {
			return false;
		}

		$funnel_data = self::get_funnel_data( $funnel_id );

		if ( ! $funnel_data || ! isset( $funnel_data['drawflow']['Home']['data'] ) || ! is_array( $funnel_data['drawflow']['Home']['data'] ) ) {
			return false;
		}

		foreach ( $funnel_data['drawflow']['Home']['data'] as $key => $data ) {
			if ( isset( $data['id'] ) && isset( $data['data']['step_id'] ) && $node_id == $data['id'] ) {
				return $data['data']['step_id'];
			}
		}
		return false;
	}


	/**
	 * Get node by step
	 *
	 * @param $funnel_id
	 * @param $step_id
	 *
	 * @return bool|int|string
	 */
	public static function get_node_id( $funnel_id, $step_id ) {
		if ( ! $funnel_id && ! $step_id ) {
			return false;
		}

		$funnel_data = self::get_funnel_data( $funnel_id );

		if ( ! $funnel_data || ! isset( $funnel_data['drawflow']['Home']['data'] ) || ! is_array( $funnel_data['drawflow']['Home']['data'] ) ) {
			return false;
		}

		foreach ( $funnel_data['drawflow']['Home']['data'] as $key => $data ) {
			if ( isset( $data['id'] ) && isset( $data['data']['step_id'] ) && $step_id == $data['data']['step_id'] ) {
				return $data['id'];
			}
		}
		return false;
	}


	/**
	 * Get funnel data
	 *
	 * @param $funnel_id
	 *
	 * @return mixed
	 *
	 * @since 2.0.5
	 */
	public static function get_funnel_data( $funnel_id ) {
		return get_post_meta( $funnel_id, '_funnel_data', true );
	}


	/**
	 * Update settings option
	 *
	 * @param $key
	 * @param $value
	 * @param bool  $network
	 *
	 * @since 1.0.0
	 */
	public static function update_admin_settings( $key, $value, $network = false ) {
		if ( $network && is_multisite() ) {
			update_site_option( $key, $value );
		} else {
			update_option( $key, $value );
		}
	}


	/**
	 * Get admin settings option
	 * by key
	 *
	 * @param $key
	 * @param bool $default
	 * @param bool $network
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 */
	public static function get_admin_settings( $key, $default = false, $network = false ) {
		if ( $network && is_multisite() ) {
			$value = get_site_option( $key, $default );
		} else {
			$value = get_option( $key, $default );
		}
		return $value;
	}


	/**
	 * Get general settings data
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function get_general_settings() {
		$default_settings = apply_filters(
			'wpfunnels_general_settings',
			array(
				'builder'             => 'gutenberg',
				'paypal_reference'    => 'off',
				'order_bump'          => 'off',
				'ab_testing'          => 'off',
				'allow_funnels'       => array( 'administrator' => 'true' ),
				'funnel_type'         => 'lead',
				'create_child_order'  => 'off',
				'disable_theme_style' => 'off',
				'enable_log_status'   => 'off',
				'enable_skip_cart'    => 'off',
				'skip_cart_for'       => 'whole',
				'uninstall_cleanup'   => 'off',
			)
		);
		$saved_settings   = self::get_admin_settings( '_wpfunnels_general_settings', $default_settings );
		return wp_parse_args( $saved_settings, $default_settings );
	}


	/**
	 * Get permalink settings data
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function get_permalink_settings() {
		$default_settings = apply_filters(
			'wpfunnels_permalink_settings',
			array(
				'structure'   => 'default',
				'step_base'   => WPFNL_STEPS_POST_TYPE,
				'funnel_base' => WPFNL_FUNNELS_POST_TYPE,
			)
		);
		$saved_settings   = self::get_admin_settings( '_wpfunnels_permalink_settings' );
		return wp_parse_args( $saved_settings, $default_settings );
	}

	/**
	 * Get optin settings data
	 *
	 * @return array|string
	 * @since  1.0.0
	 */
	public static function get_optin_settings( $handler = '' ) {
		$default_settings = apply_filters(
			'wpfunnels_optin_settings',
			array(
				'sender_name'   	=> get_bloginfo('name'),
				'sender_email'   	=> wp_get_current_user()->user_email,
				'email_subject'   	=> 'New Form Submission',
			)
		);
		$saved_settings   = self::get_admin_settings( '_wpfunnels_optin_settings' );
		$settings = wp_parse_args( $saved_settings, $default_settings );
		return $handler && isset($settings[$handler]) ? $settings[$handler] : $settings;
	}


	/**
	 * Get offer settings data
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function get_offer_settings() {
		$default_settings = apply_filters(
			'wpfunnels/get_offer_settings',
			array(
				'offer_orders'                   => 'main-order',
				'show_supported_payment_gateway' => 'off',
				'skip_offer_step'                => 'off',
				'skip_offer_step_for_free'       => 'off',
			)
		);
		$saved_settings   = self::get_admin_settings( '_wpfunnels_offer_settings' );
		return wp_parse_args( $saved_settings, $default_settings );
	}

	/**
	 * Get GTM events
	 *
	 * @return array
	 */
	public static function get_gtm_events() {
		$default_gtm_events = array(
			'add_to_cart'       => 'Add to cart',
			'begin_checkout'    => 'Begin checkout',
			'add_payment_info'  => 'Add Payment Info',
			'add_shipping_info' => 'Add Shipping Info',
			'purchase'          => 'Purchase',
			'orderbump_accept'  => 'Order Bump',
			'upsell'            => 'Upsell',
			'downsell'          => 'Downsell',
		);
		return $default_gtm_events;
	}

	public static function get_gtm_settings() {
		$default_enable_settings = array(
			'gtm_enable'       => 'off',
			'gtm_container_id' => '',
			'gtm_events'       => array(
				'add_to_cart'       => 'true',
				'begin_checkout'    => 'true',
				'add_payment_info'  => 'true',
				'add_shipping_info' => 'true',
				'purchase'          => 'true',
				'orderbump_accept'  => 'true',
				'upsell'            => 'true',
				'downsell'          => 'true',
			),
		);
		$gtm_settings            = self::get_admin_settings( '_wpfunnels_gtm' );
		return wp_parse_args( $gtm_settings, $default_enable_settings );
	}
	/**
	 * Get facebook pixel events
	 *
	 * @return array
	 */
	public static function get_facebook_events() {
		$default_fb_events = array(
			'AddPaymentInfo'   => 'Add payment info',
			'AddToCart'        => 'Add to cart',
			'InitiateCheckout' => 'Initiate checkout',
			// 'Lead' => 'Lead',
			'Purchase'         => 'Purchase',
			'ViewContent'      => 'View content',
		);
		return $default_fb_events;
	}

	public static function get_facebook_pixel_settings() {
		$default_enable_settings = array(
			'enable_fb_pixel'          => 'off',
			'facebook_pixel_id'        => '',
			'facebook_tracking_events' => array(
				'AddPaymentInfo'   => 'true',
				'AddToCart'        => 'true',
				'InitiateCheckout' => 'true',
				'Lead'             => 'true',
				'Purchase'         => 'true',
				'ViewContent'      => 'true',
			),
		);
		$facebook_pixel_setting  = self::get_admin_settings( '_wpfunnels_facebook_pixel' );
		return wp_parse_args( $facebook_pixel_setting, $default_enable_settings );
	}


	public static function get_recaptcha_settings() {
		$default_enable_settings = array(
			'enable_recaptcha'      => 'no',
			'recaptcha_site_key'    => '',
			'recaptcha_site_secret' => '',
		);
		$recaptcha_setting       = self::get_admin_settings( '_wpfunnels_recaptcha_setting' );
		return wp_parse_args( $recaptcha_setting, $default_enable_settings );
	}


	/**
	 * Get user role management settings
	 *
	 * @return array|mixed|void
	 *
	 * @since 3.1.3
	 */
	public static function get_user_role_settings() {
		global $wp_roles;

		$user_role_names = array_keys( $wp_roles->get_names() );
		$user_role_names = array_diff( $user_role_names, array( 'administrator' ) );

		$default_settings = array();

		foreach ( $user_role_names as $user_role_name ) {
			if( 'subscriber' === $user_role_name || 'customer' === $user_role_name ) {
				continue;
			}
			$default_settings[ $user_role_name ] = 'no';
		}

		$user_role_manager = self::get_admin_settings( '_wpfunnels_user_roles' );
		$user_role_manager = wp_parse_args( $user_role_manager, $default_settings );
		return $user_role_manager;
	}


	/*
	 * Get google place API key
	 *
	 * @return mixed|void
	 * @since 3.1.3
	 */
	public static function get_google_map_api_key() {
		return self::get_admin_settings( '_wpfunnels_google_map_api_key', '' );
	}


	/**
	 * Get advanced settings
	 */
	public static function get_advanced_settings() {
		$default_enable_settings = array(
			'show_supported_payment_gateway' => 'off',
		);

		$advanced_settings = self::get_admin_settings( '_wpfunnels_advanced_settings' );
		return wp_parse_args( $advanced_settings, $default_enable_settings );
	}

	/**
	 * Get UTM Parameters
	 *
	 * @return array
	 */
	public static function get_utm_params() {
		$default_utm_params = array(
			'utm_source'   => 'UTM Source',
			'utm_medium'   => 'UTM Medium',
			'utm_campaign' => 'UTM Campaign',
			'utm_content'  => 'UTM Content',
		);
		return $default_utm_params;
	}

	public static function get_utm_settings() {
		$default_enable_settings = array(
			'utm_enable'   => 'off',
			'utm_source'   => '',
			'utm_medium'   => '',
			'utm_campaign' => '',
			'utm_content'  => '',
		);
		$utm_settings            = self::get_admin_settings( '_wpfunnels_utm_params' );
		return wp_parse_args( $utm_settings, $default_enable_settings );
	}

	/**
	 * Get user roles
	 *
	 * @return array
	 * @since  2.1.7
	 */
	public static function get_user_roles() {
		global $wp_roles;
		$all_roles = $wp_roles->roles;
		return array_keys( $all_roles );
	}


	/**
	 * Get the saved builder type
	 *
	 * @return mixed|void
	 * @since  1.0.0
	 */
	public static function get_builder_type() {
		 $general_settings = self::get_general_settings();
		return $general_settings['builder'];
	}


	/**
	 * Check if wc is installed
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function is_wc_active() {
		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		} elseif ( function_exists( 'is_plugin_active' ) ) {
			if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Check if elementor is installed
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function is_elementor_active() {
		if ( in_array( 'elementor/elementor.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		} elseif ( function_exists( 'is_plugin_active' ) ) {
			if ( is_plugin_active( 'elementor/elementor.php' ) ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Check if funnel step or not
	 *
	 * @return bool
	 * @since  3.1.0
	 */
	public static function maybe_funnel_step( $step_id = null ) {
		if ( ! $step_id ) {
			$step_id = get_the_ID();
		}
		$post_type = get_post_type( $step_id );
		if ( WPFNL_STEPS_POST_TYPE === $post_type ) {
			return true;
		}
		return false;
	}


	/**
	 * Check if saved builder is activated
	 * or not
	 *
	 * @param $builder
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function is_builder_active( $builder ) {
		switch ( $builder ) {
			case 'elementor':
				return self::is_elementor_active();
				break;
			default:
				return false;
				break;
		}
	}


	/**
	 * Check if the global funnel addon is activated or not
	 *
	 * @return bool
	 * @since  2.0.4
	 */
	public static function is_global_funnel_activated() {
		return apply_filters( 'wpfunnels/is_global_funnel_activated', false );
	}


	/**
	 * Check if the funnel is global funnel
	 *
	 * @param $funnel_id
	 *
	 * @return bool
	 */
	public static function is_global_funnel( $funnel_id ) {
		if ( ! $funnel_id ) {
			return false;
		}
		return apply_filters( 'wpfunnels/is_global_funnel', false, $funnel_id );
	}


	/**
	 * Check if pro is activated/deactivated
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function is_wpfnl_pro_activated() {
		return apply_filters( 'wpfunnels/is_wpfnl_pro_active', false ) || apply_filters( 'is_wpfnl_pro_active', false );
	}


	/**
	 * Check if pro is activated/deactivated
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function is_pro_license_activated() {
		 return apply_filters( 'wpfunnels/is_pro_license_activated', false );
	}


	/**
	 * Check if the module is pro or
	 * not
	 *
	 * @param $module
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function is_pro_module( $module ) {
		$pro_modules = apply_filters( 'wpfnl_pro_modules', array() );
		return in_array( $module, $pro_modules );
	}


	/**
	 * Check if the module is exists or not
	 *
	 * @param $module_name
	 * @param string      $type
	 * @param bool        $step
	 * @param bool        $pro
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function is_module_registered( $module_name, $type = 'admin', $step = false, $pro = false ) {
		$class_name = str_replace( '-', ' ', $module_name );
		$class_name = str_replace( ' ', '', ucwords( $class_name ) );
		if ( $pro ) {
			if ( $type === 'steps' ) {
				$class_name = 'WPFunnelsPro\\Admin\\Modules\\Steps\\' . $class_name . '\Module';
			}
		} else {
			if ( $type === 'admin' ) {
				$class_name = 'WPFunnels\\Admin\\Modules\\' . $class_name . '\Module';
			} elseif ( $type === 'steps' ) {
				$class_name = 'WPFunnels\\Admin\\Modules\\Steps\\' . $class_name . '\Module';
			} else {
				$class_name = 'WPFunnels\\Modules\\Frontend\\' . $class_name . '\Module';
			}
		}
		return class_exists( $class_name );
	}


	/**
	 * Check manager permissions on REST API.
	 *
	 * @param string $object Object.
	 * @param string $context Request context.
	 *
	 * @return bool
	 * @since  2.6.0
	 */
	public static function wpfnl_rest_check_manager_permissions( $object, $context = 'read' ) {

		$objects = array(
			'settings'  => 'wpf_manage_funnels',
			'templates' => 'wpf_manage_funnels',
			'steps'     => 'wpf_manage_funnels',
			'products'  => 'wpf_manage_funnels',
		);
		return current_user_can( $objects[ $object ] );
	}


	/**
	 * Check if the provided plugin ($path) is installed or not
	 *
	 * @param $path
	 *
	 * @return bool
	 * @since  2.0.0
	 */
	public static function is_plugin_installed( $path ) {
		$plugins = get_plugins();
		return isset( $plugins[ $path ] );
	}



	/**
	 * Check if the provided plugin ($path) is installed or not
	 *
	 * @param $path
	 *
	 * @return bool
	 * @since  2.0.0
	 */
	public static function is_plugin_activated( $path ) {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';
		if ( is_plugin_active( $path ) ) {
			return true;
		}
		return false;
	}



	/**
	 * Check plugin status by path
	 *
	 * @param $path
	 * @param $slug
	 *
	 * @return string
	 *
	 * @since 2.0.1
	 */
	public static function get_plugin_action( $path, $slug ) {
		if ( 'divi-builder' === $slug ) {
			$is_divi_theme_active = self::wpfnl_is_theme_active( 'Divi' );
			if ( $is_divi_theme_active ) {
				return 'nothing';
			}
		}

		if ( 'bricks' === $slug ) {
			$maybe_bricks_theme = self::maybe_bricks_theme();
			if ( $maybe_bricks_theme ) {
				return 'nothing';
			} else {
				return 'install';
			}
		}

		if ( null === self::$installed_plugins ) {
			self::$installed_plugins = get_plugins();
		}

		if ( ! isset( self::$installed_plugins[ $path ] ) ) {
			return 'install';
		} elseif ( ! is_plugin_active( $path ) ) {
			return 'activate';
		} else {
			return 'nothing';
		}
	}

	/**
	 * Check theme is active or not
	 *
	 * @param string $theme_name
	 *
	 * @return Bool
	 */
	public static function wpfnl_is_theme_active( $theme_name ) {
		$theme = wp_get_theme(); // gets the current theme
		if ( $theme_name === $theme->name || $theme_name === $theme->parent_theme ) {
			return true;
		}
		return false;
	}


	/**
	 * Check plugin is installed or not
	 *
	 * @param $plugin_slug
	 *
	 * @return Bolean
	 */
	public static function wpfnl_check_is_plugin_installed( $plugin ) {
		$installed_plugins = get_plugins();
		return array_key_exists( $plugin, $installed_plugins ) || in_array( $plugin, $installed_plugins, true );
	}


	/**
	 * Get depenedency plugins status
	 *
	 * @return mixed|void
	 *
	 * @since 2.0.1
	 */
	public static function get_dependency_plugins_status() {
		return apply_filters(
			'wpfunnels/dependency_plugin_list',
			array(
				'elementor'    => array(
					'name'        => 'Elementor',
					'plugin_file' => 'elementor/elementor.php',
					'slug'        => 'elementor',
					'action'      => self::get_plugin_action( 'elementor/elementor.php', 'elementor' ),
				),
				'divi-builder' => array(
					'name'        => 'Divi',
					'plugin_file' => 'divi-builder/divi-builder.php',
					'slug'        => 'divi-builder',
					'action'      => self::get_plugin_action( 'divi-builder/divi-builder.php', 'divi-builder' ),
				),
				'oxygen'       => array(
					'name'        => 'Oxygen',
					'plugin_file' => 'oxygen/functions.php',
					'slug'        => 'oxygen',
					'action'      => self::get_plugin_action( 'oxygen/functions.php', 'oxygen' ),
				),
				'bricks'       => array(
					'name'        => 'Bricks',
					'plugin_file' => 'bricks',
					'slug'        => 'bricks',
					'action'      => self::get_plugin_action( 'bricks', 'bricks' ),
				),
			)
		);
	}


	/**
	 * Is there any missing plugin for wpfunnels
	 *
	 * @return string
	 *
	 * @since 2.0.1
	 */
	public static function is_any_plugin_missing() {
		if ( null === self::$installed_plugins ) {
			self::$installed_plugins = get_plugins();
		}
		$builder            = self::get_builder_type();
		$dependency_plugins = self::get_dependency_plugins_status();
		$is_missing         = 'no';

		if ( isset( $dependency_plugins[ $builder ] ) ) {
			$plugin_data = $dependency_plugins[ $builder ];
			if ( 'activate' === $plugin_data['action'] || 'install' === $plugin_data['action'] ) {
				$is_missing = 'yes';
			}
		}

		/**
		 * For support extra theme with divi
		 */
		$theme = wp_get_theme();

		if ( 'divi-builder' === $builder && ( 'Extra' === $theme->name || 'Extra' === $theme->parent_theme ) ) {
			$is_missing = 'no';
		}

		return $is_missing;
	}


	/**
	 * Recursively traverses a multidimensional array in search of a specific value and returns the
	 * array containing the value, or an
	 * null on failure.
	 *
	 * @param $search_value
	 * @param $array
	 *
	 * @return array
	 * @since  2.0.0
	 */
	public static function recursive_multidimensional_ob_array_search_by_value( $search_value, $array, $keys = array() ) {
		if ( is_array( $array ) && count( $array ) > 0 ) {
			foreach ( $array as $key => $value ) {
				$temp_keys = $keys;

				// Adding current key to search path
				array_push( $temp_keys, $key );

				// Check if this value is an array
				// with atleast one element
				if ( is_array( $value ) && count( $value ) > 0 ) {
					$widget_type = isset( $value['widgetType'] ) ? $value['widgetType'] : false;
					if ( $widget_type ) {
						if ( $widget_type === $search_value ) {
							$value['path'] = $temp_keys;
							return $value;
						} else {
							$res_path = self::recursive_multidimensional_ob_array_search_by_value(
								$search_value,
								$value['elements'],
								$temp_keys
							);
						}
						if ( $res_path != null ) {
							return $res_path;
						}
					} else {
						$res_path = self::recursive_multidimensional_ob_array_search_by_value(
							$search_value,
							$value['elements'],
							$temp_keys
						);
					}
					if ( $res_path != null ) {
						return $res_path;
					}
				}
			}
		}

		return null;
	}


	/**
	 * Check if checkout ajax or not
	 *
	 * @return bool
	 */
	public static function is_checkout_ajax() {
		if ( wp_doing_ajax() || isset( $_GET['wc-ajax'] ) ) {
			if ( isset( $_GET['wc-ajax'] ) && //phpcs:ignore
				isset( $_POST['_wcf_checkout_id'] ) //phpcs:ignore
			) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Calculate discount price
	 *
	 * @param $discount_type
	 * @param $discount_value
	 * @param $product_price
	 *
	 * @deprecated deprecated since 2.7.6
	 *
	 * @return string
	 */
	public static function calculate_discount_price( $discount_type, $discount_value, $product_price ) {

		$custom_price = $product_price;
		if ( ! empty( $discount_type ) ) {
			if ( 'discount-percentage' === $discount_type ) {
				if ( $discount_value > 0 && $discount_value <= 100 ) {
					$custom_price = $product_price - ( ( $product_price * $discount_value ) / 100 );
					$custom_price = number_format( (float) $custom_price, 2, '.', '' );
				}
			} elseif ( 'discount-price' === $discount_type ) {
				if ( $discount_value > 0 ) {
					$custom_price = $product_price - $discount_value;
				}
			}
		}
		return $custom_price;
	}


	/**
	 * Calculate discount price
	 *
	 * @param $discount_type
	 * @param $discount_value
	 * @param $product_price
	 *
	 * @return string
	 */
	public static function calculate_discount_amount( $discount_type, $discount_value, $product_price ) {

		if ( $discount_type !== 'original' && ! empty( $discount_type ) ) {
			$discount = '';
			if ( 'discount-percentage' === $discount_type ) {
				if ( $discount_value > 0 && $discount_value <= 100 ) {
					$discount = ( ( $product_price * $discount_value ) / 100 );
				}
			} elseif ( 'discount-price' === $discount_type ) {
				if ( $discount_value > 0 ) {
					$discount = $discount_value;

				}
			}
			return number_format( $discount, 2 );
		}

		return false;
	}


	/**
	 * Get attributes for wpfunnels body wrapper
	 *
	 * @param string $template
	 *
	 * @return string
	 */
	public static function get_template_container_atts( $template = '' ) {
		$attributes  = apply_filters( 'wpfunnels/page_container_atts', array() );
		$atts_string = '';
		foreach ( $attributes as $key => $value ) {
			if ( ! $value ) {
				continue;
			}
			if ( true === $value ) {
				$atts_string .= esc_html( $key ) . ' ';
			} else {
				$atts_string .= sprintf( '%s="%s" ', esc_html( $key ), esc_attr( $value ) );
			}
		}
		return $atts_string;
	}


	/**
	 * Get funnel link
	 *
	 * @param $funnel_id
	 *
	 * @return Mix
	 */
	public static function get_funnel_link( $funnel_id ) {
		if ( ! $funnel_id ) {
			return;
		}
		$steps = self::get_steps( $funnel_id );
		if ( $steps && is_array( $steps ) ) {
			$first_step    = reset( $steps );
			$first_step_id = $first_step['id'];
			return get_the_permalink( $first_step_id );
		}
		return home_url();
	}


	/**
	 * Get wc fragment
	 *
	 * @param array $data
	 *
	 * @return array
	 */
	public static function get_wc_fragments( $data = array() ) {
		ob_start();
		woocommerce_order_review();
		$woocommerce_order_review = ob_get_clean();

		ob_start();
		woocommerce_checkout_payment();
		$woocommerce_checkout_payment = ob_get_clean();

		$response = array(
			'cart_total'          => WC()->cart->total,
			'cart_total_currency' => WC()->cart->get_cart_total(),
			'wc_custom_fragments' => 'yes',
			'fragments'           => apply_filters(
				'woocommerce_update_order_review_fragments',
				array(
					'.woocommerce-checkout-review-order-table' => $woocommerce_order_review,
					/*'.woocommerce-checkout-payment' => $woocommerce_checkout_payment,*/
				)
			),
		);

		if ( ! empty( $data ) ) {
			$response['wpfunnels_data'] = $data;
		}

		return $response;
	}


	/**
	 * Get supported builders
	 *
	 * @return array
	 */
	public static function get_supported_builders() {
		$builders = array(
			'gutenberg'    => 'Gutenberg',
			'elementor'    => 'Elementor',
			'divi-builder' => 'Divi builder',
			'oxygen'       => 'Oxygen',
			'bricks'       => 'Bricks',
			'other'        => 'Other',
		);
		return apply_filters( 'wpfunnels/supported_builders', $builders );
	}

	/**
	 * Get custom url instead of deafult tahnkyou page
	 *
	 * @param mixed $step_id
	 *
	 * @return [type]
	 */
	public static function custom_url_for_thankyou_page( $step_id ) {

		$isThankyou  = self::check_if_this_is_step_type_by_id( $step_id, 'thankyou' );
		$isCustomUrl = get_post_meta( $step_id, '_wpfnl_thankyou_is_custom_redirect', true );
		$isDirect    = get_post_meta( $step_id, '_wpfnl_thankyou_is_direct_redirect', true );

		if ( $isThankyou && $isCustomUrl === 'on' ) {
			$url = get_post_meta( $step_id, '_wpfnl_thankyou_custom_redirect_url', true );
			if ( $url ) {

				if ( $isDirect === 'off' ) {
					$redirectAfterSec = get_post_meta( $step_id, '_wpfnl_thankyou_set_time', true ) ? get_post_meta( $step_id, '_wpfnl_thankyou_set_time', true ) : 5;
					header( 'refresh:' . $redirectAfterSec . ';url=' . $url );
				} else {
					// unsell 'wpfunnels_automation_data' cookie and trigger 'wpfunnels/trigger_automation' hook
					self::unset_site_cookie( $step_id, 'wpfunnels_automation_data', 'wpfunnels/trigger_automation' );
					return $url;
				}
			}
			return false;
		}
		return false;
	}

	/**
	 * Check product has perfect variation or not
	 *
	 * @param $variation_id
	 *
	 * @return Boolean
	 */
	public static function is_perfect_variations( $variation_id ) {

		$blank_attr = array();
		$response   = array(
			'status' => true,
			'data'   => array(),
		);
		$product    = wc_get_product( $variation_id );
		if ( $product ) {
			$parent_id = $product->get_parent_id();
			$_product  = wc_get_product( $parent_id );
			if ( $_product ) {
				$attrs      = self::get_product_attr( $_product );
				$attributes = $product->get_attributes();
				if ( ! empty( $attributes ) ) {
					foreach ( $attributes as $attribute_key => $attribute_value ) {

						if ( ! $attribute_value ) {
							$blank_attr[ $attribute_key ] = $attrs[ $attribute_key ];
							$response['status']           = false;
						}
					}
				}
			}
		}
		$response['data'] = $blank_attr;
		return $response;
	}


	/**
	 * Get attributes of product
	 *
	 * @param Object $produt
	 */
	public static function get_product_attr( $product ) {
		$attributes = $product->get_attributes();
		$attr_array = array();
		foreach ( $attributes as $attribute_key => $attribute_value ) {

			$attribute_name = str_replace( 'attribute_', '', $attribute_key );
			$attr_value     = $product->get_attribute( $attribute_name );
			$attr_value     = strtolower( $attr_value );

			if ( strpos( $attr_value, '|' ) ) {
				$attr_array[ $attribute_key ] = explode( '|', $attr_value );
			} else {
				$attr_array[ $attribute_key ] = explode( ',', $attr_value );
			}
		}
		return $attr_array;

	}



	/**
	 * Remove site cookie
	 *
	 * @param $step_id, $cookie_name, $trigger_hook, $funnel_id
	 */
	public static function unset_site_cookie( $step_id, $cookie_name, $trigger_hook = '', $funnel_id = '' ) {

		if ( ! $funnel_id ) {
			$funnel_id = self::get_funnel_id_from_step( $step_id );
		}

		if ( ! $funnel_id ) {
			return false;
		}

		$cookie = isset( $_COOKIE[ $cookie_name ] ) ? json_decode( wp_unslash( $_COOKIE[ $cookie_name ] ), true ) : array();
		if ( ! isset( $cookie['funnel_id'] ) ) {
			$cookie['funnel_id'] = $funnel_id;
		}
		$cookie['funnel_status'] = 'successful';

		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			if ( $trigger_hook ) {
				do_action( $trigger_hook, $cookie );
			}
		}
		// unset cookie
		ob_start();
		if ( isset( $_COOKIE[ $cookie_name ] ) ) {
			setcookie( $cookie_name, null, strtotime( '-1 days' ), '/', COOKIE_DOMAIN );
		}
		ob_end_flush();
	}


	/**
	 * Get formated product name
	 *
	 * @param Object $product
	 *
	 * @return String
	 */
	public static function get_formated_product_name( $product, $formatted_attr = array() ) {
		$_product        = wc_get_product( $product );
		$each_child_attr = array();
		$_title          = '';
		if ( $_product ) {
			if ( ! $formatted_attr ) {
				if ( 'variable' === $_product->get_type() || 'variation' === $_product->get_type() || 'subscription_variation' === $_product->get_type() || 'variable-subscription' === $_product->get_type() ) {
					$attr_summary = $_product->get_attribute_summary();
					$attr_array   = explode( ',', $attr_summary );

					foreach ( $attr_array as $ata ) {
						$attr              = strpbrk( $ata, ':' );
						$each_child_attr[] = $attr;
					}
				}
			} else {
				foreach ( $formatted_attr as $attr ) {
					$each_child_attr[] = ucfirst( $attr );
				}
			}
			if ( $each_child_attr ) {
				$each_child_attr_two = array();
				foreach ( $each_child_attr as $eca ) {
					$each_child_attr_two[] = str_replace( ': ', ' ', $eca );
				}
				$_title = $_product->get_title() . ' - ';
				$_title = $_title . implode( ', ', $each_child_attr_two );
			} else {
				$_title = $_product->get_title();
			}
		}

		return $_title;
	}


	/**
	 * Get page builder of a specific funnel by step Id from postmeta
	 *
	 * @param $funnel_id
	 *
	 * @return String $builder_name
	 *
	 * @since 2.0.5
	 */
	public static function get_page_builder_by_step_id( $funnel_id ) {
		$steps        = self::get_steps( $funnel_id );
		$builder_name = '';
		if ( isset( $steps[0] ) ) {
			$first_step_id = $steps[0]['id'];
			// check builder is elementor or not
			$elementor_page = get_post_meta( $first_step_id, '_elementor_edit_mode', true );

			// check builder is divi or not
			$divi_page = get_post_meta( $first_step_id, '_et_pb_use_builder', true );

			// check Oxygen builder is not
			$oxygen_page = get_post_meta( $first_step_id, 'ct_builder_shortcodes', true );

			// check builder is bricks or not
			$bricks_page = get_post_meta( $first_step_id, '_bricks_editor_mode', true );

			if ( $elementor_page ) {
				$builder_name = 'elementor';
			} elseif ( 'on' === $divi_page ) {
				$builder_name = 'divi-builder';
			} elseif ( 'bricks' === $bricks_page ) {
				$builder_name = 'bricks';
			} elseif ( ! empty( $oxygen_page ) ) {
				$builder_name = 'oxygen';
			} else {
				$builder_name = 'gutenberg';
			}

			if ( $builder_name ) {
				return $builder_name;
			}
		}
		return $builder_name;
	}


	/**
	 * Get checkout section heading settings
	 */
	public static function get_checkout_section_heading_settings( $type = '', $step_id = '' ) {

		if ( self::is_pro_license_activated() ) {
			if ( $step_id ) {
				$get_settings = get_post_meta( $step_id, '_wpfunnels_edit_field_additional_settings', true );
				if ( ! empty( $get_settings ) ) {
					if ( $type === 'billing' ) {

						$settings = array(
							'custom_heading' => $get_settings['custom_billing_heading'],
						);
						return $settings;
					}
					if ( $type === 'shipping' ) {
						$settings = array(
							'custom_heading' => $get_settings['custom_shipping_heading'],
						);
						return $settings;
					}

					if ( $type === 'order' ) {
						$settings = array(
							'custom_heading' => $get_settings['custom_order_detail_heading'],
						);
						return $settings;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Check webhook addon activated or not
	 *
	 * @return Boolean
	 */
	public static function is_webhook_activated() {
		if ( is_plugin_active( 'wpfunnels-pro-webhook/wpfunnels-pro-webhook.php' ) ) {
			return true;
		}
		return false;
	}


	/**
	 * Check webhook addon license activated or not
	 *
	 * @return Boolean
	 */
	public static function is_webhook_license_activated() {

		if ( is_plugin_active( 'wpfunnels-pro/wpfnl-pro.php' ) ) {
			return true;
		}
		return false;
	}


	/**
	 * Get gbf supported addons
	 */
	public static function get_supported_addons() {

		$addons = array(
			'global_funnel' => array(
				'features' => array(
					'trigger_options' => array(
						''         => __( 'Please select type..', 'wpfnl' ),
						'category' => __( 'Product category is ..', 'wpfnl' ),
						'product'  => __( 'Product is ..', 'wpfnl' ),
					),
				),
			),
		);

		if ( defined( 'WPFNL_PRO_GB_VERSION' ) && version_compare( WPFNL_PRO_GB_VERSION, '1.0.7', '>=' ) ) {
			$addons['global_funnel']['features']['trigger_options']['all_product'] = __( 'Any product is selected', 'wpfnl' );
		}
		return apply_filters( 'wpfunnels/supported-addons', $addons );
	}

	/**
	 * Get supported GBF offer condition
	 */
	public static function get_supported_gbf_offer_condition() {
		$conditions = array(
			'specificProduct' => 'Specific Product',
		);
		return apply_filters( 'wpfunnels/supported-gbf-offer-condition', $conditions );
	}


	/**
	 * Get product categories for GBF offer condition
	 */
	public static function get_categories_gbf_offer_condition() {
		$categories = array();
		if ( self::is_wc_active() ) {
			$orderby            = 'name';
			$order              = 'asc';
			$hide_empty         = false;
			$cat_args           = array(
				'orderby'    => $orderby,
				'order'      => $order,
				'hide_empty' => $hide_empty,
			);
			$product_categories = get_terms( 'product_cat', $cat_args );
			if ( ! empty( $product_categories ) ) {
				foreach ( $product_categories as $category ) {
					$categories[ $category->slug ] = $category->name;
				}
			}
		}
		return $categories;
	}


	public static function oxygen_builder_version_capability() {
		if ( defined( 'CT_VERSION' ) && version_compare( CT_VERSION, '3.2', '>=' ) ) {
			return true;
		}
		return false;
	}

	public static function get_funnel_utm_settings( $funnel_id = null ) {
		if ( $funnel_id ) {
			$default_enable_settings = array(
				'utm_enable'   => 'off',
				'utm_source'   => '',
				'utm_medium'   => '',
				'utm_campaign' => '',
				'utm_content'  => '',
			);

			$utm_settings = get_post_meta( $funnel_id, '_wpfunnels_utm_params', true );
			return wp_parse_args( $utm_settings, $default_enable_settings );
		}
		return false;
	}

	public static function get_funnel_settings( $funnel_id = null ) {

		if ( $funnel_id ) {

			$utm_settings = self::get_funnel_utm_settings( $funnel_id );
			$is_fb_pixel  = get_post_meta( $funnel_id, '_wpfunnels_disabled_fb_pixel', true ) ? get_post_meta( $funnel_id, '_wpfunnels_disabled_fb_pixel', true ) : 'no';
			$is_gtm       = get_post_meta( $funnel_id, '_wpfunnels_disabled_gtm', true ) ? get_post_meta( $funnel_id, '_wpfunnels_disabled_gtm', true ) : 'no';

			return array(
				'utm_settings' => $utm_settings,
				'is_fb_pixel'  => $is_fb_pixel,
				'is_gtm'       => $is_gtm,
			);
		}
		return false;

	}


	/**
	 * Check discount is applicable or not in checkout page
	 */
	public static function discount_on_checkout( $step_id = null ) {
		if ( $step_id ) {
			$discount = get_post_meta( $step_id, '_wpfnl_checkout_discount_main_product', true );
			if ( $discount ) {
				return $discount;
			}
		}
		return false;
	}


	/**
	 * Calculate main product total amount in checkout page
	 *
	 * @param Array  $checkout_product
	 * @param Object $cart_content
	 * @param String $step_id
	 *
	 * @return Mix
	 */
	public static function calculate_main_product_total( $checkout_product = null, $cart_content = null, $step_id = '' ) {

		if ( $checkout_product && $cart_content ) {
			$total = 0;

			foreach ( $cart_content as $key => $content ) {
				if ( isset( $content['wpfnl_order_bump'] ) && $content['wpfnl_order_bump'] ) {
					continue;
				}
				$product_id = '';
				if ( ! empty( $content['variation_id'] ) ) {
					$product_id = $content['variation_id'];
				}

				if ( ! $product_id ) {
					$product_id = isset( $content['product_id'] ) ? $content['product_id'] : '';
				}

				$checkout_product = maybe_unserialize( $checkout_product );
				$key              = array_search( $product_id, array_column( $checkout_product, 'id' ) );
				if ( $key !== false ) {
					if ( wc_tax_enabled() ) {
						if ( ! wc_prices_include_tax() ) {
							$content['line_total'] = number_format( $content['line_total'], 2 );
							$content['line_total'] = filter_var( $content['line_total'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
							$total                 = $total + $content['line_total'];
						} else {

							$content['line_total'] = number_format( $content['line_total'], 2 );
							$content['line_total'] = filter_var( $content['line_total'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
							$total                 = $total + $content['line_total'] + $content['line_tax'];

						}
					} else {
						$content['line_total'] = number_format( $content['line_total'], 2 );
						$content['line_total'] = filter_var( $content['line_total'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
						$total                 = $total + $content['line_total'] + $content['line_tax'];
					}
				}
			}

			$total = number_format( $total, 2 );
			$total = filter_var( $total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
			return $total;
		}
		return false;
	}



	/**
	 * Calculate cart total amount in checkout page
	 *
	 * @param Array  $checkout_product
	 * @param Object $cart_content
	 * @param String $step_id
	 *
	 * @return bool|int|float
	 */
	public static function calculate_cart_total( $checkout_product = null, $cart_content = null, $step_id = '' ) {

		if ( $checkout_product && $cart_content ) {
			$total = 0;

			foreach ( $cart_content as $key => $content ) {
				$product_id = $content['variation_id'] ? $content['variation_id'] : $content['product_id'];

				if ( wc_tax_enabled() ) {
					if ( ! wc_prices_include_tax() ) {
						$content['line_total'] = number_format( $content['line_total'], 2 );
						$content['line_total'] = filter_var( $content['line_total'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
						$total                 = $total + $content['line_total'];
					} else {
						$content['line_total'] = number_format( $content['line_total'], 2 );
						$content['line_total'] = filter_var( $content['line_total'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
						$total                 = $total + $content['line_total'] + $content['line_tax'];

					}
				} else {
					$content['line_total'] = number_format( $content['line_total'], 2 );
					$content['line_total'] = filter_var( $content['line_total'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
					$total                 = $total + $content['line_total'] + $content['line_tax'];

				}
			}
			$total = number_format( $total, 2 );
			$total = filter_var( $total, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
			return $total;
		}
		return false;
	}



	/**
	 * Check array type ( multi-dimentional or one dimentional )
	 *
	 * @param Array
	 *
	 * @return Bool
	 */
	public static function check_array_is_multidimentional( $multi_array = null ) {

		if ( $multi_array ) {
			foreach ( $multi_array as $array ) {
				if ( is_array( $array ) ) {
					return true;
				} else {
					return false;
				}
			}
			return true;
		}
		return false;
	}


	/**
	 * Migrate old Order bump settings with multiple order bump
	 */
	public static function migrate_order_bump( $prev_settings = null, $step_id = null ) {
		if ( $prev_settings && $step_id ) {
			$multi_settings = array();
			if ( ! self::check_array_is_multidimentional( $prev_settings ) ) {
				$multi_settings = array(
					$prev_settings,
				);
				update_post_meta( $step_id, 'order-bump-settings', $multi_settings );
				return $multi_settings;
			}
		}
		return false;
	}


	/**
	 * N:B test case should be implemented
	 * Get order bump settings by step id
	 */
	public static function get_ob_settings( $step_id ) {

		$all_settings = get_post_meta( $step_id, 'order-bump-settings', true ) ? get_post_meta( $step_id, 'order-bump-settings', true ) : array();
		$is_multiple  = self::check_array_is_multidimentional( $all_settings );

		if ( ! $is_multiple && $all_settings ) {
			$all_settings['name'] = 'Order bump';
			$all_settings         = self::migrate_order_bump( $all_settings, $step_id );
		}

		if ( is_array( $all_settings ) && count( $all_settings ) > 0 ) {
			foreach ( $all_settings as $key => $settings ) {
				if ( ! isset( $all_settings[ $key ]['discountOption'] ) && 'original' !== $all_settings[ $key ]['discountOption'] ) {
					$product = wc_get_product( $all_settings[ $key ]['product'] );
					if ( $product ) {
						if ( $product->is_on_sale() ) {
							$price = wc_format_sale_price( $product->get_regular_price() * $all_settings[ $key ]['quantity'], $product->get_sale_price() ? $product->get_sale_price() * $all_settings[ $key ]['quantity'] : $product->get_regular_price() * $all_settings[ $key ]['quantity'] );
						} else {
							$price = $product->get_regular_price();
							if ( is_plugin_active( 'woocommerce-subscriptions/woocommerce-subscriptions.php' ) ) {
								$signUpFee = \WC_Subscriptions_Product::get_sign_up_fee( $product );
								$price     = $price + $signUpFee;
							}
							$price = wc_price( $price * $all_settings[ $key ]['quantity'] );
						}
						$all_settings[ $key ]['htmlPrice'] = $price ? $price : $all_settings[ $key ]['htmlPrice'];
					}
				}
			}
			return $all_settings;
		}

		return array();
	}


	/**
	 * Check if funnel canvas or not
	 *
	 * @return bool
	 * @since  2.3.8
	 */
	public static function is_funnel_canvas_window() {
		if ( is_admin() && isset( $_GET['page'] ) && 'edit_funnel' === $_GET['page'] ) {
			return true;
		}
		return false;
	}


	/**
	 * Get all funnel from post
	 *
	 * @return $funnels
	 * @since  2.4.4
	 */
	public static function get_all_funnels( $status = 'publish' ) {

		if ( 'all' == $status ) {
			$arg = array(
				'post_type'   => WPFNL_FUNNELS_POST_TYPE,
				'fields'      => 'ID',
				'orderby'     => 'date',
				'order'       => 'ASC',
				'numberposts' => -1,
			);
		} else {
			$arg = array(
				'post_type'   => WPFNL_FUNNELS_POST_TYPE,
				'post_status' => $status,
				'fields'      => 'ID',
				'orderby'     => 'date',
				'order'       => 'ASC',
				'numberposts' => -1,
			);
		}

		$funnels = get_posts( $arg );
		return $funnels;
	}


	/**
	 * Get all funnel from post
	 *
	 * @return $funnels
	 * @since  2.4.4
	 */
	public static function get_all_steps() {

		$steps = get_posts(
			array(
				'post_type'   => 'wpfunnel_steps',
				'post_status' => 'publish',
				'fields'      => 'ID',
				'orderby'     => 'date',
				'order'       => 'ASC',
			)
		);
		return $steps;
	}


	/**
	 * Add type meta for each funnel
	 *
	 * @since 2.4.4
	 */
	public static function add_type_meta() {

		$is_added = get_option( '_wpfnl_added_type_meta' );
		if ( ! $is_added ) {
			update_option( '_wpfnl_added_type_meta', 'yes' );
			$funnels = self::get_all_funnels();
			foreach ( $funnels as $funnel ) {
				$type = get_post_meta( $funnel->ID, '_wpfnl_funnel_type', true );
				if ( ! $type ) {
					update_post_meta( $funnel->ID, '_wpfnl_funnel_type', 'wc' );
				}
			}
		}

	}


	/**
	 * Enable lms settings
	 *
	 * @return Bool
	 * @since  2.4.5
	 */
	public static function is_enable_lms_settings() {
		$status = get_option( 'wpfunnels_pro_lms_license_status' );
		if ( $status === 'active' ) {
			return apply_filters( 'wpfunnels/is_enable_lms_settings', false );
		}
		return false;
	}

	/**
	 * Check if lms addon activated or not
	 *
	 * @return Bool
	 * @since  2.4.5
	 */
	public static function is_lms_addon_active() {

		if ( in_array( 'wpfunnels-pro-lms/wpfunnels-pro-lms.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		} elseif ( function_exists( 'is_plugin_active' ) ) {
			if ( is_plugin_active( 'wpfunnels-pro-lms/wpfunnels-pro-lms.php' ) ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Get checkout tabs column name
	 *
	 * @return Array
	 * @since  2.4.5
	 */
	public static function get_checkout_columns( $step_id ) {

		$funnel_id = get_post_meta( $step_id, '_funnel_id', true );
		$columns   = array(
			'product-name'       => 'Product',
			'product-price'      => 'Unit Price',
			'calculate-operator' => '',
			'product-quantity'   => 'Quantity',
			'total-price'        => 'Total Price',
			'product-action'     => 'Actions',
		);

		return apply_filters( 'wpfunnels/checkout_columns', $columns, $funnel_id );
	}


	/**
	 * Supported order bump position
	 *
	 * @return Array
	 * @since  2.4.5
	 */
	public static function supported_orderbump_position( $step_id ) {
		$funnel_id = get_post_meta( $step_id, '_funnel_id', true );

		$positions = array(
			array(
				'name'  => 'Before Order Details',
				'value' => 'before-order',
			),
			array(
				'name'  => 'After Order Details',
				'value' => 'after-order',
			),
			array(
				'name'  => 'Before Checkout Details',
				'value' => 'before-checkout',
			),
			array(
				'name'  => 'After Customer Details',
				'value' => 'after-customer-details',
			),
			array(
				'name'  => 'Before Payment Options',
				'value' => 'before-payment',
			),
			array(
				'name'  => 'After Payment Options',
				'value' => 'after-payment',
			),
			array(
				'name'  => 'Pop-up offer',
				'value' => 'popup',
			),
		);
		if ( $funnel_id ) {
			return apply_filters( 'wpfunnels/ob_positions', $positions, $funnel_id );
		}

		return $positions;

	}

	/**
	 * Get time zone in string
	 *
	 * @return String
	 * @since  2.4.6
	 */
	public static function wpfnl_timezone_string() {

		// If site timezone string exists, return it.
		$timezone = get_option( 'timezone_string' );
		if ( $timezone ) {
			return $timezone;
		}

		// Get UTC offset, if it isn't set then return UTC.
		$utc_offset = floatval( get_option( 'gmt_offset', 0 ) );
		if ( ! is_numeric( $utc_offset ) || 0.0 === $utc_offset ) {
			return 'UTC';
		}

		// Adjust UTC offset from hours to seconds.
		$utc_offset = (int) ( $utc_offset * 3600 );

		// Attempt to guess the timezone string from the UTC offset.
		$timezone = timezone_name_from_abbr( '', $utc_offset );
		if ( $timezone ) {
			return $timezone;
		}

		// Last try, guess timezone string manually.
		foreach ( timezone_abbreviations_list() as $abbr ) {
			foreach ( $abbr as $city ) {
				// WordPress restrict the use of date(), since it's affected by timezone settings, but in this case is just what we need to guess the correct timezone.
				if ( (bool) date( 'I' ) === (bool) $city['dst'] && $city['timezone_id'] && intval( $city['offset'] ) === $utc_offset ) { // phpcs:ignore WordPress.DateTime.RestrictedFunctions.date_date
					return $city['timezone_id'];
				}
			}
		}

		// Fallback to UTC.
		return 'UTC';
	}



	/**
	 * Get timezone offset in seconds.
	 *
	 * @since  2.4.6
	 * @return float
	 */
	public static function wpfnl_timezone_offset() {
		$timezone = get_option( 'timezone_string' );
		if ( $timezone ) {
			$timezone_object = new DateTimeZone( $timezone );
			return $timezone_object->getOffset( new DateTime( 'now' ) );
		} else {
			return floatval( get_option( 'gmt_offset', 0 ) ) * HOUR_IN_SECONDS;
		}
	}


	/**
	 * Check if integrations addon activated or not
	 *
	 * @return Bool
	 * @since  2.4.7
	 */
	public static function is_integrations_addon_active() {
		if ( in_array( 'wpfunnels-pro-integrations/wpfunnels-pro-integrations.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
			return true;
		} elseif ( function_exists( 'is_plugin_active' ) ) {
			if ( is_plugin_active( 'wpfunnels-pro-integrations/wpfunnels-pro-integrations.php' ) ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Get template types
	 *
	 * @return array
	 * @since  2.4.11
	 */
	public static function get_template_types() {
		$general_settings = self::get_general_settings();
		if ( self::is_wc_active() ) {
			$types = array(
				array(
					'slug'  => 'wc',
					'label' => 'Woo Templates',
				),
				array(
					'slug'  => 'lead',
					'label' => 'Lead Gen Templates',
				),
			);
		} else {
			$types = array(
				array(
					'slug'  => 'lead',
					'label' => 'Lead Gen Templates',
				),
			);
		}

		if ( isset( $general_settings['funnel_type'] ) && 'lead' === $general_settings['funnel_type'] ) {
			$types = array(
				array(
					'slug'  => 'lead',
					'label' => 'Lead Gen Templates',
				),
			);
		}
		return apply_filters( 'wpfunnels/modify_template_type', $types );

	}


	/**
	 * Get global funnel type
	 *
	 * @return Mix
	 */
	public static function get_global_funnel_type() {
		$general_settings = self::get_general_settings();
		if ( isset( $general_settings['funnel_type'] ) ) {
			return $general_settings['funnel_type'];
		}
		return false;
	}


	/**
	 * May be allow to create sales funnel
	 *
	 * @return Bool
	 */
	public static function maybe_allow_sales_funnel() {
		if ( self::is_wc_active() || self::is_lms_addon_active() ) {
			return true;
		}
		return false;
	}

	/**
	 * Retrieve all user role
	 *
	 * @return Array
	 */
	public static function get_all_user_roles() {
		global $wp_roles;

		$all_roles      = isset( $wp_roles->roles ) ? $wp_roles->roles : array();
		$editable_roles = apply_filters( 'editable_roles', $all_roles );

		return $editable_roles;
	}


	/**
	 * Retrive all the product tags
	 *
	 * @return array
	 * @since  2.4.11
	 */
	public static function get_all_tags() {
		$term_array = array();
		if ( self::is_wc_active() ) {
			$terms = get_terms( 'product_tag' );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				foreach ( $terms as $term ) {

					$term_array[] = array(
						'id'   => isset( $term->term_id ) ? $term->term_id : '',
						'name' => isset( $term->name ) ? $term->name : '',
					);
				}
			}
		}
		return $term_array;
	}

	/**
	 * Get all shiiping classes
	 *
	 * @return array
	 * @since  2.4.10
	 */
	public static function get_shipping_classes() {

		if ( self::is_wc_active() ) {
			$shipping_classes = WC()->shipping()->get_shipping_classes();
			if ( $shipping_classes ) {
				return $shipping_classes;
			}
		}
		return array();
	}


	/**
	 * Maybe global funnel
	 *
	 * @param String
	 *
	 * @return String
	 *
	 * @since 2.4.14
	 */
	public static function maybe_global_funnel( $step_id = '' ) {
		if ( $step_id ) {
			$funnel_id = get_post_meta( $step_id, '_funnel_id', true );
			$is_gbf    = get_post_meta( $funnel_id, 'is_global_funnel', true );
			if ( $is_gbf ) {
				return $is_gbf;
			}
		}
		return 'no';
	}


	/**
	 * Get Woocommerce billing/shipping countries
	 *
	 * @return array
	 * @since  2.4.18
	 */
	public static function get_countries() {
		if ( self::is_wc_active() ) {
			$wc_countries = new \WC_Countries();
			$countries    = $wc_countries->get_countries();
			if ( $countries ) {
				return $countries;
			}
		}
		return array();
	}


	/**
	 * Check the checkout layout is express or not
	 *
	 * @return Bool
	 * @since  2.4.18
	 */
	public static function maybe_express_checkout( $checkout_id = '' ) {
		if ( ! $checkout_id ) {
			global $post;
			if ( ! $post ) {
				return false;
			}
			if ( wp_doing_ajax() || isset( $_GET['wc-ajax'] ) ) {
				$checkout_id = self::get_checkout_id_from_post( $_POST );
			} else {
				$checkout_id = $post->ID;
			}
			if ( ! $checkout_id ) {
				$checkout_id = get_the_ID();
			}
		}

		$layout = get_post_meta( $checkout_id, '_wpfnl_checkout_layout', true );

		return 'wpfnl-express-checkout' === $layout;
	}



	/**
	 * Sanitize request data
	 *
	 * @param $data
	 *
	 * @return Mix
	 */
	public static function get_sanitized_get_post( $data = array() ) {
		if ( is_array( $data ) && ! empty( $data ) ) {
			return filter_var_array( $data, FILTER_SANITIZE_FULL_SPECIAL_CHARS );
		}
		return array(
			'get'     => filter_input_array( INPUT_GET, FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
			'post'    => filter_input_array( INPUT_POST, FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
			'request' => filter_var_array( $_REQUEST, FILTER_SANITIZE_FULL_SPECIAL_CHARS ),
		);
	}



	/**
	 * Update funnel type to lead
	 *
	 * @since 2.5.1
	 */
	public static function update_funnel_type_to_lead() {

		$saved_settings = self::get_admin_settings( '_wpfunnels_general_settings' );
		if ( isset( $saved_settings['funnel_type'] ) ) {
			$saved_settings['funnel_type'] = 'lead';
			update_option( '_wpfunnels_general_settings', $saved_settings );
		}
	}


	/**
	 * Delete template transients
	 *
	 * @since 2.5.1
	 */
	public static function wpfnl_delete_transient() {
		delete_option( WPFNL_TEMPLATES_OPTION_KEY . '_wc' );
		delete_option( WPFNL_TEMPLATES_OPTION_KEY . '_lms' );
		delete_option( WPFNL_TEMPLATES_OPTION_KEY . '_lead' );

		delete_transient( 'wpfunnels_remote_template_data_wc_' . WPFNL_VERSION );
		delete_transient( 'wpfunnels_remote_template_data_lms_' . WPFNL_VERSION );
		delete_transient( 'wpfunnels_remote_template_data_lead_' . WPFNL_VERSION );
	}


	/**
	 * Get all the published funnel lists
	 *
	 * @return Array $funnels
	 * @since  2.5.6
	 */
	public static function get_funnel_list() {
		$funnels                    = get_posts(
			array(
				'post_type'   => WPFNL_FUNNELS_POST_TYPE,
				'post_status' => 'publish',
				'numberposts' => -1,
				'fields'      => 'ID',
				'orderby'     => 'date',
				'order'       => 'ASC',
			)
		);
		$formatted_funnel_data[' '] = __( 'Select funnel', 'wpfnl' );
		if ( is_array( $funnels ) ) {
			foreach ( $funnels as $funnel ) {
				$is_gbf = get_post_meta( $funnel->ID, 'is_global_funnel', true );
				if ( ! $is_gbf || 'no' === $is_gbf ) {
					$formatted_funnel_data[ self::get_funnel_link( $funnel->ID ) ] = $funnel->post_title;
				}
			}
		}
		return $formatted_funnel_data;
	}

	/**
	 * Check the log status is enabled or not
	 *
	 * @return Bool
	 * @since  2.5.9
	 */
	public static function maybe_logger_enabled() {
		$general_settings = get_option( '_wpfunnels_general_settings' );
		if ( $general_settings && isset( $general_settings['enable_log_status'] ) ) {
			return 'on' === $general_settings['enable_log_status'];
		}
		return false;
	}



	/**
	 * Check user permission to allow WPFunnels
	 *
	 * @param Array $permitted_role
	 *
	 * @return String $role
	 * @since  2.6.2
	 */
	public static function role_permission_to_allow_wpfunnel( $permitted_role = array() ) {
		return 'wpf_manage_funnels';
	}


	/**
	 * Get supported step type
	 * Landing, Checkout, Thankyou steps are supported for free
	 *
	 * @return Array $types
	 * @since  2.5.7
	 */
	public static function get_supported_step_type() {
		$types = array(
			array(
				'type' => 'landing',
				'name' => 'Landing',
			),
			array(
				'type' => 'checkout',
				'name' => 'Checkout',
			),
			array(
				'type' => 'thankyou',
				'name' => 'Thankyou',
			),
		);

		return apply_filters( 'wpfunnels/supported_step_type', $types );
	}


	/**
	 * Check if mint crm is installed
	 *
	 * @return bool
	 * @since  1.0.0
	 */
	public static function is_mint_mrm_active() {
		return apply_filters( 'is_mail_mint_pro_active', false );
	}


	/**
	 * Check if mint automation exist for a funnel by funnel id.
	 *
	 * @param int $funnel_id
	 * @return bool
	 * @since  1.0.0
	 */
	public static function maybe_automation_exist_for_a_funnel( $funnel_id ) {
		return apply_filters( 'wpfunnels/maybe_automation_exist_for_a_funnel', false, $funnel_id );
	}

	/**
	 * Supported steps for allow settings
	 *
	 * @return array
	 * @since  2.6.6
	 */
	public static function get_settings_steps() {
		$settings_steps = array(
			'upsell',
			'downsell',
			'checkout',
			'thankyou',
			'delay',
			'sendMail',
			'createUser',
			'addTag',
			'addList',
			'removeTag',
			'removeList',
			'twilioSendMessage',
			'wpf_optin_submit',
			'wpf_order_placed',
			'wpf_cta_triggered',
			'wpf_order_bump_accepted',
			'wpf_offer_trigger',
		);

		return apply_filters( 'wpfunnels/supported_settings_steps', $settings_steps );
	}


	/**
	 * Supported steps for allow only settings
	 *
	 * @return array
	 * @since  2.6.6
	 */
	public static function get_only_settings_steps() {
		$settings_steps = array(
			'delay',
			'sendMail',
			'createUser',
			'addTag',
			'addList',
			'removeTag',
			'removeList',
			'twilioSendMessage',
			'wpf_optin_submit',
			'wpf_order_placed',
			'wpf_cta_triggered',
			'wpf_order_bump_accepted',
			'wpf_offer_trigger',
		);

		return apply_filters( 'wpfunnels/supported_only_settings_steps', $settings_steps );
	}


	/**
	 *
	 */
	public static function get_default_automation() {
		$automation_data = array(
			'name'         => 'automation-1',
			'status'       => 'active',
			'author'       => 1,
			'trigger_name' => 'wpf-offer-trigger',
			'steps'        => array(
				array(
					'step_id'      => 'mg3e1i',
					'key'          => 'wp_user_login',
					'type'         => 'trigger',
					'settings'     => array(),
					'next_step_id' => 'zytv4',
				),
				array(
					'step_id'      => 'zytv4',
					'key'          => 'createUser',
					'type'         => 'action',
					'settings'     => array(),
					'next_step_id' => '9w8vjh',
				),
				array(
					'step_id'      => '9w8vjh',
					'key'          => 'addTag',
					'type'         => 'action',
					'settings'     => array(
						'tag_settings' => array(
							'tags' => array(
								array(
									'id'    => 3,
									'title' => 'demo',
								),
							),
						),
					),
					'next_step_id' => '',
				),
			),
			'created_ago'  => '3 seconds',
		);

		return $automation_data;
	}


	/**
	 * Get mint triggers
	 *
	 * @return array
	 */
	public static function get_mint_triggers() {
		$triggers = array(
			'wpf_optin_submit',
			'wpf_order_placed',
			'wpf_cta_triggered',
			'wpf_order_bump_accepted',
			'wpf_offer_trigger',
		);

		return apply_filters( 'wpfunnels/mint_triggers', $triggers );
	}


	/**
	 * Get mint actions
	 *
	 * @return array
	 */
	public static function get_mint_actions() {
		$actions = array(
			'delay',
			'sendMail',
			'createUser',
			'addTag',
			'addList',
			'removeTag',
			'removeList',
			'twilioSendMessage',
		);

		return apply_filters( 'wpfunnels/mint_actions', $actions );
	}


	/**
	 * Get all tags/list from mint
	 *
	 * @return array
	 */
	public static function get_mint_contact_groups( $type = 'tags' ) {
		$class_name = 'Mint\\MRM\\DataBase\\Models\\ContactGroupModel';
		if ( class_exists( $class_name ) ) {

			$groups = $class_name::get_all_lists_or_tags( $type );
			if ( $groups && is_array( $groups ) ) {
				$formatted_groups = array();
				$i                = 1;
				foreach ( $groups as $group ) {
					$data = array(
						'index'   => $i,
						'title'   => $group['title'],
						'id'      => $group['id'],
						'checked' => false,
					);
					array_push( $formatted_groups, $data );
					$i++;
				}

				return $formatted_groups;
			}
		}
		return array();
	}


	/**
	 * Get selected steps
	 *
	 * @param string $type
	 * @param int    $funnel_id
	 *
	 * @return array
	 */
	public static function get_selected_steps( $type, $funnel_id ) {
		if ( $funnel_id ) {
			$steps = get_post_meta( $funnel_id, '_steps', true );
			if ( $steps && is_array( $steps ) ) {
				$i               = 1;
				$formatted_steps = array();
				$step_types      = array( $type );
				if ( 'landing' == $type ) {
					$step_types = array( $type, 'custom' );
				}
				foreach ( $steps as $step ) {
					if ( in_array( $step['step_type'], $step_types ) ) {
						$data = array(
							'id'    => $i,
							'title' => $step['name'],
							'value' => $step['id'],
						);
						array_push( $formatted_steps, $data );
						$i++;
					}
				}

				return $formatted_steps;
			}
		}
		return array();
	}


	/**
	 * Get all order bump of a funnel
	 *
	 * @param int $funnel_id
	 *
	 * @return array
	 */
	public static function get_all_ob( $funnel_id ) {
		if ( $funnel_id ) {
			$steps = self::get_steps( $funnel_id );
			if ( $steps && is_array( $steps ) ) {
				$key = array_search( 'checkout', array_column( $steps, 'step_type' ) );
				if ( false !== $key ) {
					if ( isset( $steps[ $key ]['id'] ) ) {
						$checkout_step_id = $steps[ $key ]['id'];
						$order_bumps      = get_post_meta( $checkout_step_id, 'order-bump-settings', true );
						if ( $order_bumps && is_array( $order_bumps ) ) {
							$i             = 1;
							$formatted_obs = array();
							foreach ( $order_bumps as $index => $ob ) {
								if ( is_array( $ob ) && isset( $ob['name'] ) ) {
									$data = array(
										'id'    => $i,
										'title' => $ob['name'],
										'value' => $index,
									);
									array_push( $formatted_obs, $data );
									$i++;
								}
							}
							return $formatted_obs;
						}
					}
				}
			}
		}
		return array();
	}



	/**
	 * Get sequences from campaign table
	 *
	 * @return array
	 * @since  1.0.0
	 */
	public static function get_sequences() {
		if ( self::is_mint_mrm_active() && class_exists( 'Mint\\MRM\\DataBase\\Tables\\CampaignSchema' ) ) {

			global $wpdb;
			$class_name     = 'Mint\\MRM\\DataBase\\Tables\\CampaignSchema';
			$campaign_table = $wpdb->prefix . $class_name::$campaign_table;
			$results = $wpdb->get_results( $wpdb->prepare( "SELECT id as value, title as label FROM $campaign_table WHERE type = %s AND status = %s ", 'automation', 'created' ), ARRAY_A ); // phpcs:ignore.
			$default        = array(
				array(
					'value' => '',
					'label' => 'Select Sequence',
				),
			);
			if ( ! empty( $results ) ) {
				return array_merge( $default, $results );
			} else {
				return $default;
			}
		}
		return array();
	}


	/**
	 * Get CFE deafult fields
	 *
	 * @return array
	 * @since 2.7.0
	 */
	public static function get_cfe_default_fields() {
		if ( self::is_wc_active() ) {
			$fields                = array();
			$billing_default_field = WC()->countries->get_address_fields();
			foreach ( $billing_default_field as $key => $sf ) {
				$billing_default_field[ $key ]['enable'] = 1;
				$billing_default_field[ $key ]['show']   = 1;
				$billing_default_field[ $key ]['delete'] = 0;
				if ( ! ( isset( $sf['type'] ) ) ) {
					$billing_default_field[ $key ]['type'] = null;
				}
				if ( ! ( isset( $sf['placeholder'] ) ) ) {
					$billing_default_field[ $key ]['placeholder'] = null;
				}
				if ( ! ( isset( $sf['label'] ) ) ) {
					$billing_default_field[ $key ]['label'] = null;
				}
				if ( ! ( isset( $sf['validate'] ) ) ) {
					$billing_default_field[ $key ]['validate'] = null;
				}
				if ( ! ( isset( $sf['default'] ) ) ) {
					$billing_default_field[ $key ]['default'] = null;
				}
			}

			$countries               = new \WC_Countries();
			$shipping_default_fields = $countries->get_address_fields( $countries->get_base_country(), 'shipping_' );
			foreach ( $shipping_default_fields as $key => $sf ) {
				$shipping_default_fields[ $key ]['enable'] = 1;
				$shipping_default_fields[ $key ]['show']   = 1;
				$shipping_default_fields[ $key ]['delete'] = 0;
				if ( ! ( isset( $sf['type'] ) ) ) {
					$shipping_default_fields[ $key ]['type'] = null;
				}
				if ( ! ( isset( $sf['placeholder'] ) ) ) {
					$shipping_default_fields[ $key ]['placeholder'] = null;
				}
				if ( ! ( isset( $sf['label'] ) ) ) {
					$shipping_default_fields[ $key ]['label'] = null;
				}
				if ( ! ( isset( $sf['validate'] ) ) ) {
					$shipping_default_fields[ $key ]['validate'] = null;
				}
				if ( ! ( isset( $sf['default'] ) ) ) {
					$shipping_default_fields[ $key ]['default'] = null;
				}
			}

			$order_comment = array(
				'type'        => 'textarea',
				'class'       => array( 'notes' ),
				'label'       => __( 'Order notes', 'woocommerce' ),
				'placeholder' => esc_attr__(
					'Notes about your order, e.g. special notes for delivery.',
					'woocommerce'
				),
				'name'        => 'order_comments',
				'required'    => false,
				'enable'      => 1,
				'show'        => 1,
				'default'     => null,
				'validate'    => null,
				'delete'      => 0,
			);

			$additional_default_fields = array(
				'order_comments' => $order_comment,
			);

			$fields = array(
				'billing'    => $billing_default_field,
				'shipping'   => $shipping_default_fields,
				'additional' => $additional_default_fields,
			);
			return $fields;
		}
	}

	/**
	 * Generates and updates the first step id of the funnel
	 *
	 * @param int|string $funnel_id Funnel id.
	 * @since 2.8.0
	 */
	public static function generate_first_step( $funnel_id, $steps = array() ) {
		if ( $funnel_id ) {
			$steps = ! empty( $steps ) ? $steps : get_post_meta( $funnel_id, '_steps', true ); // Get step order

			if ( empty( $steps ) ) {
				$steps = get_post_meta( $funnel_id, '_steps_order', true ); // Get step order
			}
			$priority_order = array( 'landing', 'custom', 'checkout', 'upsell', 'downsell', 'thankyou' );
			$first_step     = array(
				'id'        => null,
				'step_type' => '',
			);

			if ( is_array( $steps ) && ! empty( $steps ) ) {
				foreach ( $steps as $step ) {
					if (
						isset( $step['step_type'], $step['id'] )
						&& ( ! $first_step['id'] || ( array_search( $step['step_type'], $priority_order ) < array_search( $first_step['step_type'], $priority_order ) ) )
					) {
						$first_step['id']        = $step['id'];
						$first_step['step_type'] = $step['step_type'];
					}
				}
				self::update_funnel_first_step( $funnel_id, $first_step['id'] );
			}
		}
	}

	/**
	 * Get the first step of a funnel
	 *
	 * @param int|string $funnel_id Funnel id.
	 * @return string|int
	 * @since 2.8.0
	 */
	public static function get_first_step( $funnel_id ) {
		return get_post_meta( $funnel_id, '_first_step', true );
	}

	/**
	 * Updates the first step id of the funnel
	 *
	 * @param int|string $funnel_id Funnel id.
	 * @param int|string $first_step_id First step id of the funnel.
	 * @return void
	 */
	public static function update_funnel_first_step( $funnel_id, $first_step_id ) {
		update_post_meta( $funnel_id, '_first_step', $first_step_id );
	}


	/**
	 * Mail mint email
	 *
	 * @return mix
	 */
	public static function get_mailmint_email() {
		if ( self::is_mint_mrm_active() ) {
			$email_settings = get_option( '_mrm_email_settings' );
			if ( isset( $email_settings['from_email'] ) ) {
				return $email_settings['from_email'];
			}
		}
		return false;
	}


	/**
	 * Mail mint email
	 *
	 * @return mix
	 */
	public static function get_mailmint_email_settings() {
		if ( self::is_mint_mrm_active() ) {
			return get_option( '_mrm_email_settings', \Mint\MRM\Utilites\Helper\Email::default_email_settings() );
		}
		return false;
	}


	/**
	 * Mail mint email
	 *
	 * @return mix
	 */
	public static function get_mailmint_twillo_settings() {
		if ( self::is_mint_mrm_active() ) {
			$settings = get_option( '_mint_integration_settings', array() );
			$twilio   = isset( $settings['twilio'] ) ? $settings['twilio'] : array();
			return is_array( $twilio ) && ! empty( $twilio ) ? $twilio : array(
				'account_sid'   => '',
				'auth_token'    => '',
				'from_number'   => '',
				'is_integrated' => false,
			);
		}
		return false;
	}


	/**
	 * Maybe duplicate step
	 *
	 * @param $step_id Step ID
	 *
	 * @since 2.7.7
	 * @return bool
	 */
	public static function maybe_duplicate_step( $step_id ) {

		if ( $step_id ) {
			$is_duplicate = get_post_meta( $step_id, '_is_duplicate', true );
			if ( 'yes' === $is_duplicate ) {
				return true;
			}
		}
		return false;
	}


	/**
	 * Retrieves all created coupons from WooCommerce.
	 * This function fetches all coupon posts of the 'shop_coupon' post type that are currently published in WooCommerce.
	 *
	 * @since 2.7.9
	 * @return array An array of coupon codes and their corresponding names.
	 */
	public static function get_all_coupons() {
		global $wpdb;
		$table_name   = $wpdb->prefix . 'posts';
		$coupon_type  = 'shop_coupon';
		$post_status  = 'publish';
		$query        = $wpdb->prepare(
			"SELECT post_title FROM $table_name WHERE post_type = %s AND post_status = %s ORDER BY post_name ASC LIMIT 100",
			$coupon_type,
			$post_status
		);
		$coupon_codes = array();
		$coupons      = $wpdb->get_col( $query );
		if ( is_array( $coupons ) ) {
			foreach ( $coupons as $coupon ) {
				$coupon_codes[] = array(
					'name' => $coupon,
				);
			}
		}
		return $coupon_codes;
	}


	/**
	 * Retrieves the applied coupon for a given step ID.
	 * This function checks if a step ID is provided and retrieves the applied coupon associated with that step.
	 * If no step ID is provided or the applied coupon is not found, it returns false.
	 *
	 * @param int $step_id The ID of the step for which to retrieve the applied coupon.
	 *
	 * @since 2.7.9
	 * @return string|false The selected coupon code if found and auto-coupon feature is
	 */
	public static function get_applied_coupon( $step_id ) {
		if ( ! $step_id ) {
			return false;
		}
		$auto_coupon = get_post_meta( $step_id, '_wpfnl_checkout_auto_coupon', true );
		if ( isset( $auto_coupon['enableAutoCoupon'], $auto_coupon['selectedCoupon'] ) && 'yes' === $auto_coupon['enableAutoCoupon'] && $auto_coupon['selectedCoupon'] ) {
			return $auto_coupon['selectedCoupon'];
		}
		return false;
	}

	/**
	 * Get step IDs for a given funnel.
	 *
	 * Retrieves an array of step IDs associated with a specific funnel, including A/B testing variations.
	 *
	 * @param int $funnel_id The ID of the funnel.
	 * @return array An array of step IDs.
	 * @since 2.7.10
	 */
	public static function get_step_ids( $funnel_id ) {
		$steps = self::get_steps( $funnel_id );

		$all_steps_including_ab     = array();
		$all_steps_including_ab_ids = array();
		if ( is_array( $steps ) ) {
			foreach ( $steps as $step ) {
				if ( isset( $step['id'] ) ) {
					$all_steps_including_ab = get_post_meta( $step['id'], '_wpfnl_ab_testing_start_settings', true );
					if ( isset( $all_steps_including_ab['variations'] ) ) {
						$ids                        = array_column( $all_steps_including_ab['variations'], 'id' );
						$all_steps_including_ab_ids = array_merge( $all_steps_including_ab_ids, $ids );
					}
				}
			}
		}

		return $all_steps_including_ab_ids;
	}

	/**
	 * Validates the order owner's authorization.
	 *
	 * @param WC_Order $order The order object to validate.
	 * @return bool
	 * @since 2.7.13
	 */
	public static function is_valid_order_owner( $order ) {

		if ( ! self::is_wc_active() || false === is_a( $order, 'WC_Order' ) ) {
			return false;
		}

		// If it's not a guest order, current user can pay but only if it's their own order,
		// or they are an admin or shop manager or they are in a same session(for guest).

		$order_has_customer  = ! empty( $order->get_customer_id() );
		$is_authorized_owner = false;

		$has_owner = ! empty( WC()->session->get( 'wpfnl_order_owner' ) );

		if ( $order_has_customer ) {
			$is_order_owned_by_current_user = $order->get_customer_id() === get_current_user_id();

			$is_authorized_owner = $is_order_owned_by_current_user || current_user_can( 'manage_woocommerce' );
		}

		if ( ! $is_authorized_owner && ! $has_owner ) {
			return false;
		}
		return true;
	}


	/**
	 * Capture a screenshot of a webpage given its URL and save it locally.
	 *
	 * @param string $url     The URL of the webpage to capture the screenshot.
	 * @param int    $step_id The ID of the step associated with the screenshot.
	 *
	 * @return string The full URL of the captured screenshot if successful, an empty string otherwise.
	 *
	 * @since 2.9.0
	 */
	public static function get_screenshot_from_url( $url, $step_id ) {
		try {
			$file_name    = uniqid();
			$path         = wp_upload_dir();
			$baseurl      = $path['baseurl'] . '/wpfunnels';
			$path         = $path['basedir'] . '/wpfunnels';
			$default_file = $path . '/index.php';
			if ( ! file_exists( $path ) ) {
				wp_mkdir_p( $path );
			}

			if ( ! file_exists( $default_file ) ) {
				$file_handle = @fopen( trailingslashit( $path ) . 'index.php', 'wb' );
				if ( $file_handle ) {
					fwrite( $file_handle, $file['content'] );
					fclose( $file_handle );
				}
			}

			\Spatie\Browsershot\Browsershot::url( $url )
				->waitUntilNetworkIdle()
				->windowSize( 1280, 800 )
				->ignoreHttpsErrors()
				->save( $screenshotPath );

			// Update the post meta with the screenshot's base URL.
			update_post_meta( $step_id, 'wpfnl_step_screenshot_baseurl', $path . '/' . $file_name . '.png' );

			// Return the full URL of the captured screenshot.
			return $baseurl . '/' . $file_name . '.png';
		} catch ( Exception $e ) {
			return '';
		}
	}


	/**
	 * Update the screenshot URL for a step by its ID.
	 *
	 * @param int    $step_id The ID of the step.
	 * @param string $url     The new screenshot URL to be set.
	 *
	 * @return bool True if the update is successful, false otherwise.
	 *
	 * @since 2.9.0
	 */
	public static function maybe_update_screenshot_url_by_step_id( $step_id, $url ) {
		if ( ! $step_id || ! $url ) {
			return false;
		}

		// Update the post meta for the step with the new screenshot URL.
		update_post_meta( $step_id, 'wpfnl_step_screenshot_url', $url );

		return true;
	}


	/**
	 * Update the modified date of a step's screenshot identified by its ID.
	 *
	 * @param int $step_id The ID of the step.
	 *
	 * @return bool True on success, false on failure.
	 *
	 * @since 2.9.0
	 */
	public static function maybe_update_screenshot_modified_date_by_step_id( $step_id ) {
		if ( ! $step_id ) {
			return false;
		}
		if ( ! self::get_screenshot_url_by_step_id( $step_id ) ) {
			return false;
		}
		// Update the post meta for the step with the new screenshot modified date.
		update_post_meta( $step_id, 'wpfnl_step_screenshot_modified_date', date( 'Y-m-d H:i:s' ) );

		return true;
	}


	/**
	 * Get the screenshot URL for a step by its ID.
	 *
	 * @param int $step_id The ID of the step.
	 *
	 * @return string|false The screenshot URL if found, false if not found.
	 *
	 * @since 2.9.0
	 */
	public static function get_screenshot_url_by_step_id( $step_id ) {
		if ( ! $step_id ) {
			return false;
		}

		// Get the post meta for the step's screenshot URL.
		$url = get_post_meta( $step_id, 'wpfnl_step_screenshot_url', true );

		if ( ! $url ) {
			return false;
		}

		return $url;
	}


	/**
	 * Get the modified date of a step identified by its ID.
	 *
	 * @param int $step_id The ID of the step.
	 *
	 * @return string|bool The modified date in 'Y-m-d H:i:s' format on success, false on failure.
	 *
	 * @since 2.9.0
	 */
	public static function get_screenshot_modified_date_by_step_id( $step_id ) {
		if ( ! $step_id ) {
			return false;
		}

		// Get the post meta for the step's screenshot modified date.
		$date = get_post_meta( $step_id, 'wpfnl_step_screenshot_modified_date', true );

		if ( ! $date ) {
			return false;
		}

		return $date;
	}


	/**
	 * Get the modified date of a step by its ID.
	 *
	 * @param int $step_id The ID of the step.
	 *
	 * @return string|false The modified date if found, false if not found.
	 *
	 * @since 2.9.0
	 */
	public static function get_the_modified_date_of_step( $step_id ) {
		if ( ! $step_id ) {
			return false;
		}

		// Get the modified date of the step.
		$date = get_the_modified_date( 'Y-m-d H:i:s', $step_id );

		if ( ! $date ) {
			return false;
		}

		return $date;
	}


	/**
	 * Delete the screenshot file associated with a step identified by its ID.
	 *
	 * @param int $step_id The ID of the step.
	 *
	 * @return bool True if the screenshot file is deleted successfully, false otherwise.
	 */
	public static function maybe_delete_screenshot_by_step_id( $step_id ) {

		if ( ! $step_id ) {
			return false;
		}

		// Update the screenshot URL to get the file path.
		$path = get_post_meta( $step_id, 'wpfnl_step_screenshot_baseurl', true );

		// Check if the file exists and is readable.
		if ( ! file_exists( $path ) ) {
			return false;
		}

		// Try to delete the file.
		unlink( $path );
		return true;
	}


	/**
	 * Get the URL of a Step with Optional UTM Parameters.
	 *
	 * This function retrieves the URL of a specified step within a funnel. It takes a funnel ID
	 * and a step ID as input and returns the URL of the step. If UTM tracking is enabled for the
	 * funnel, this function appends the UTM parameters to the URL as query parameters.
	 *
	 * @since 1.6.0
	 *
	 * @param int $funnel_id The ID of the funnel.
	 * @param int $step_id   The ID of the step whose URL needs to be retrieved.
	 *
	 * @return string|false The URL of the step with optional UTM parameters or false if the URL is not found.
	 */
	public static function get_step_url( $funnel_id, $step_id ) {
		if ( ! $funnel_id || ! $step_id ) {
			return false;
		}

		$url = get_the_permalink( $step_id );

		if ( ! $url ) {
			return false;
		}

		// Get UTM settings for the funnel.
		$utm_settings = self::get_funnel_utm_settings( $funnel_id );

		// Check if UTM parameters are enabled.
		if ( ! is_array( $utm_settings ) || ! isset( $utm_settings['utm_enable'] ) || 'on' !== $utm_settings['utm_enable'] ) {
			return $url;
		}

		unset( $utm_settings['utm_enable'] );
		$url = add_query_arg( $utm_settings, $url );
		$url = strtolower( $url );

		return $url;
	}


	/**
	 * Check if conditional logic is enabled for a specific step.
	 *
	 * This function checks whether the conditional logic feature is enabled for a given step
	 * by checking the '_wpfnl_maybe_enable_condition' post meta value associated with the step.
	 * If the value is 'yes', then the conditional logic is considered enabled; otherwise, it is not.
	 *
	 * @param int $step_id The ID of the step to check for conditional logic.
	 * @return bool True if conditional logic is enabled, false otherwise.
	 *
	 * @since 2.9.0
	 */
	public static function maybe_enable_condition( $step_id ) {
		if ( ! $step_id ) {
			return false;
		}

		$is_enabled = get_post_meta( $step_id, '_wpfnl_maybe_enable_condition', true );
		if ( 'yes' !== $is_enabled ) {
			return false;
		}

		return true;
	}


	/**
	 * Get the conditions associated with a specific step.
	 *
	 * This function retrieves the conditions linked to a given step by querying the '_wpfnl_step_conditions'
	 * post meta associated with that step. If conditions are found and are in an array format, they are returned;
	 * otherwise, false is returned to indicate that no conditions or invalid conditions are available.
	 *
	 * @param int $step_id The ID of the step to retrieve conditions for.
	 * @return array|false An array of conditions if available and valid, false otherwise.
	 *
	 * @since 2.9.0
	 */
	public static function get_conditions( $step_id ) {
		if ( ! $step_id ) {
			return false;
		}

		$conditions = get_post_meta( $step_id, '_wpfnl_step_conditions', true );
		if ( empty( $conditions ) || ! is_array( $conditions ) ) {
			return false;
		}

		return $conditions;

	}


	/**
	 * Get the next step to be executed conditionally after the current step.
	 *
	 * This function retrieves the next step that should be executed after the current step
	 * if certain conditions are met. The next step is retrieved by querying the '_wpfnl_next_step_after_condition'
	 * post meta associated with the given step. If a valid array containing the next step information is found,
	 * it is returned; otherwise, false is returned to indicate that no next step or invalid data is available.
	 *
	 * @param int $step_id The ID of the step to retrieve the conditional next step for.
	 * @return array|false An array containing information about the next step if available and valid, false otherwise.
	 *
	 * @since 2.9.0
	 */
	public static function get_conditional_next_step( $step_id ) {
		if ( ! $step_id ) {
			return false;
		}

		$next_step = get_post_meta( $step_id, '_wpfnl_next_step_after_condition', true );
		if ( empty( $next_step ) || ! is_array( $next_step ) ) {
			return false;
		}

		return $next_step;

	}


	/**
	 * Get the next step to be executed based on conditional criteria.
	 *
	 * This function determines the next step to be executed in a funnel based on conditional criteria.
	 * If conditional criteria are not enabled for the step, it retrieves the next step using the
	 * Wpfnl_functions::get_next_step method. If conditional criteria are enabled, it checks the conditions
	 * using the WPFunnels\Conditions\Wpfnl_Condition_Checker class. If the conditions match, the true branch's
	 * next step is returned; otherwise, the false branch's next step is returned.
	 *
	 * @param int    $funnel_id The ID of the funnel to which the step belongs.
	 * @param int    $step_id   The ID of the current step.
	 * @param string $order     Optional. The order in which the step is executed. Default is empty.
	 * @param string $checker   Optional. The checker to be used for condition checking. Default is 'accept'.
	 * @return bool|array An array containing information about the next step and its type. if funnel id or step id is missing then it return false;
	 *
	 * @since 2.9.0
	 */
	public static function get_next_conditional_step( $funnel_id, $step_id, $order = '', $checker = 'accept' ) {

		if ( ! $funnel_id || ! $step_id ) {
			return false;
		}

		$is_condition_enable = self::maybe_enable_condition( $step_id );
		if ( ! $is_condition_enable ) {
			return self::get_next_step( $funnel_id, $step_id );
		}
		$condition             = \WPFunnels\Conditions\Wpfnl_Condition_Checker::getInstance();
		$condition_matched     = $condition->check_condition( $funnel_id, $order, $step_id, $step_id, $checker );
		$conditional_next_step = self::get_conditional_next_step( $step_id );

		if ( ! is_array( $conditional_next_step ) || empty( $conditional_next_step ) ) {
			return self::get_next_step( $funnel_id, $step_id, $condition_matched );
		}

		if ( $condition_matched && empty( $conditional_next_step['true'] ) ) {
			return self::get_next_step( $funnel_id, $step_id, $condition_matched );
		} elseif ( ! $condition_matched && empty( $conditional_next_step['false'] ) ) {
			return self::get_next_step( $funnel_id, $step_id, $condition_matched );
		}

		$next_node = array(
			'step_id'   => $condition_matched ? $conditional_next_step['true'] : $conditional_next_step['false'],
			'step_type' => $condition_matched ? get_post_meta( $conditional_next_step['true'], '_step_type', true ) : get_post_meta( $conditional_next_step['false'], '_step_type', true ),
		);

		return $next_node;
	}



	/**
	 * Update a meta key with a new value for a given step.
	 *
	 * @param int    $step_id    The ID of the step for which to update the meta.
	 * @param string $meta_key   The key of the meta data to update.
	 * @param mixed  $meta_value The new value to set for the meta key.
	 *
	 * @return bool True on success, false on failure.
	 *
	 * @since 3.0.0
	 */
	public static function update_meta( $step_id, $meta_key, $meta_value ) {
		if ( ! $step_id || ! $meta_key ) {
			return false;
		}
		update_post_meta( $step_id, $meta_key, $meta_value );
		return true;
	}


	/**
	 * Delete a meta key and its associated value for a given step.
	 *
	 * @param int    $step_id  The ID of the step from which to delete the meta.
	 * @param string $meta_key The key of the meta data to delete.
	 *
	 * @return bool True on success, false on failure.
	 * @since 3.0.0
	 */
	public static function delete_meta( $step_id, $meta_key ) {
		if ( ! $step_id || ! $meta_key ) {
			return false;
		}
		delete_post_meta( $step_id, $meta_key );
		return true;
	}
	/**
	 * Check if the current page is using the Gutenberg editor.
	 *
	 * This function checks whether the current page in the WordPress admin area is using the Gutenberg editor. It does so by checking the availability of a specific function associated with Gutenberg. If this function exists, it is likely that the Gutenberg editor is in use.
	 *
	 * @return bool True if the current page is using the Gutenberg editor, false otherwise.
	 * @since 2.8.12
	 */
	private static function is_gutenberg_edit_page() {
		if ( function_exists( 'wp_enqueue_script' ) && function_exists( 'wp_enqueue_style' ) ) {
			// Check if Gutenberg scripts and styles are enqueued
			return wp_script_is( 'wp-blocks' ) && wp_script_is( 'wp-element' ) && wp_style_is( 'wp-edit-blocks' );
		}
		return false;
	}

	/**
	 * Check if the current page is an Elementor edit page.
	 *
	 * This function checks whether the current page in the WordPress admin area is an edit page created with the Elementor page builder plugin. It does so by inspecting the 'action' query parameter in the URL. If the 'action' is set to 'elementor', it is likely an edit page created with Elementor.
	 *
	 * @return bool True if the current page is an Elementor edit page, false otherwise.
	 * @since 2.8.12
	 */
	private static function is_elementor_edit_page() {
		// Check if the 'action' query parameter is set to 'elementor'.
		if ( defined( 'ELEMENTOR_VERSION' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
			return true;
		}
		return false;
	}

	/**
	 * Check if the current page is an edit page created with the Divi Builder.
	 *
	 * This function checks whether the current page in the WordPress admin area is an edit page created with the Divi Builder plugin. It does so by checking the availability of specific functions associated with Divi Builder. If these functions exist, it is likely an edit page created with Divi Builder.
	 *
	 * @return bool True if the current page is an edit page created with Divi Builder, false otherwise.
	 * @since 2.8.12
	 */
	private static function is_divi_edit_page() {
		// Check if the Divi Builder's assets are enqueued by verifying the presence of specific functions.
		return is_callable( 'et_pb_enqueue_main_scripts' ) && is_callable( 'et_theme_builder_overrides' );
	}

	/**
	 * Check if the current page is an Oxygen Builder edit page.
	 *
	 * This function checks whether the current page in the WordPress admin area is an Oxygen Builder edit page. It does so by verifying if specific Oxygen assets (scripts and styles) are enqueued. If the assets are enqueued, it is likely that the current page is an Oxygen edit page.
	 *
	 * @return bool True if the current page is an Oxygen edit page, false otherwise.
	 * @since 2.8.12
	 */
	private static function is_oxygen_edit_page() {
		// Check if Oxygen's assets are enqueued
		return wp_script_is( 'ct-automation' ) || wp_style_is( 'ct-select2' ) || 'ct_render_shortcode' === filter_input( INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS ) || filter_input( INPUT_GET, 'ct_builder', FILTER_SANITIZE_FULL_SPECIAL_CHARS ) || filter_input( INPUT_GET, 'ct_inner', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
	}

	/**
	 * Check if the current page is being edited using a specific page builder or any builder if not specified.
	 *
	 * This function checks whether the current page in the WordPress admin area is being edited using a specific page builder, such as Elementor, Divi, Oxygen, or Gutenberg. If no specific builder is provided, it checks for any of the supported page builders.
	 *
	 * @param string|null $builder (Optional) The name of the page builder to check (e.g., 'elementor', 'divi', 'oxygen', 'gutenberg'). If not specified, checks for any supported page builder.
	 * @return bool True if the current page is being edited with the specified page builder or any supported builder, false otherwise.
	 * @since 2.8.12
	 */
	public static function is_builder_edit_page( $builder = null ) {
		switch ( $builder ) {
			case 'elementor':
				return self::is_elementor_edit_page();
			case 'divi':
				return self::is_divi_edit_page();
			case 'oxygen':
				return self::is_oxygen_edit_page();
			case 'gutenberg':
				return self::is_gutenberg_edit_page();
			default:
				// Check for any supported page builder.
				return self::is_elementor_edit_page() || self::is_gutenberg_edit_page() || self::is_divi_edit_page() || self::is_oxygen_edit_page() || 'edit' === filter_input( INPUT_GET, 'action', FILTER_SANITIZE_SPECIAL_CHARS );
		}
	}


	/**
	 * Maybe remote funnel
	 *
	 * @return bool
	 * @since 3.0.6
	 */
	public static function maybe_remote_funnel() {
		if ( defined( 'REMOTE_FUNNEL_VERSION' ) ) {
			return 'yes';
		}
		return 'no';
	}


	/**
	 * Removes a disconnected addstep node from the funnel data.
	 *
	 * @param array $funnel_data The funnel data.
	 * @param int   $funnel_id   The ID of the funnel.
	 * @return array The updated funnel data.
	 */
	public static function remove_disconnected_addstep_node( $funnel_data, $funnel_id ) {
		if ( ! $funnel_id || ! isset( $funnel_data['drawflow']['Home']['data'] ) || empty( $funnel_data['drawflow']['Home']['data'] ) ) {
			return $funnel_data;
		}

		$count = count( $funnel_data['drawflow']['Home']['data'] );

		if ( $count <= 1 ) {
			return $funnel_data;
		}

		// Loop through each step in funnel
		foreach ( $funnel_data['drawflow']['Home']['data'] as $key => &$step ) {
			// Find corresponding step in step id data
			if ( 'addstep' === $step['data']['step_type'] ) {
				$maybeEmptyInput  = false;
				$maybeEmptyOutput = false;
				if ( empty( $step['inputs']['input_1']['connections'] ) ) {
					$maybeEmptyInput = true;
				}

				if ( empty( $step['inputs'] ) ) {
					$maybeEmptyInput = true;
				}

				if ( empty( $step['outputs'] ) ) {
					$maybeEmptyOutput = true;
				}

				if ( empty( $step['outputs']['output_1']['connections'] ) ) {
					$maybeEmptyOutput = true;
				}
				if ( $maybeEmptyInput && $maybeEmptyOutput ) {
					unset( $funnel_data['drawflow']['Home']['data'][ $key ] );
					update_post_meta( $funnel_id, '_funnel_data', $funnel_data );
				}
			}
		}

		return $funnel_data;
	}


	/**
	 * Get all static text
	 *
	 * @return array
	 * @since 3.0.13
	 */
	public static function get_text() {
		return array(
			'error_occurred'                              => __( 'Error Occurred!', 'wpfnl' ),
			'stats'                                       => __( 'Stats', 'wpfnl' ),
			'stats_tooltip_txt'                           => __( 'Toggle to get a quick view of visits & conversion of steps right on the canvas.', 'wpfnl' ),
			'find_your_templates'                         => __( 'Find your templates', 'wpfnl' ),
			'enable_global_funnel'                        => __( 'Enable Global Funnel', 'wpfnl' ),
			'enable_gbf_tooltip_txt'                      => __( 'Enabling this will convert this funnel into a conditional global funnel and you will have define conditions to trigger the funnel right from your WooCommerce store.', 'wpfnl' ),
			'save'                                        => __( 'Save', 'wpfnl' ),
			'webhook'                                     => __( 'Webhook', 'wpfnl' ),
			'analytics'                                   => __( 'Analytics', 'wpfnl' ),
			'integrations'                                => __( 'Integrations', 'wpfnl' ),
			'publish'                                     => __( 'Publish', 'wpfnl' ),
			'draft'                                       => __( 'Draft', 'wpfnl' ),
			'delete'                                      => __( 'Delete', 'wpfnl' ),
			'trash'                                       => __( 'Trash', 'wpfnl' ),
			'deleting'                                    => __( 'Deleting...', 'wpfnl' ),
			'settings'                                    => __( 'Settings', 'wpfnl' ),
			'youtube_video'                               => __( 'YouTube Video', 'wpfnl' ),
			'documentation'                               => __( 'Documentation', 'wpfnl' ),
			'blog'                                        => __( 'Blog', 'wpfnl' ),
			'select_template_industries'                  => __( 'Select Template Industries', 'wpfnl' ),
			'select_template_builder'                     => __( 'Select Template Builder', 'wpfnl' ),
			'upload_image'                                => __( 'Upload image', 'wpfnl' ),
			'upload_image_tooltip'                        => __( 'Uploade Funnel Featured Image', 'wpfnl' ),
			'remove_image'                                => __( 'Remove image', 'wpfnl' ),
			'enter_seo_title'                             => __( 'Enter seo title', 'wpfnl' ),
			'enter_seo_desc'                              => __( 'Enter seo description', 'wpfnl' ),
			'save_data'                                   => __( 'Save Data', 'wpfnl' ),
			'want_add_new_step'                           => __( 'Do you want to add a new step?', 'wpfnl' ),
			'what_change_you_need'                        => __( 'What Changes Do You Need in this Position?', 'wpfnl' ),
			'step_has_dependency'                         => __( 'The step you are deleting has a dependency. You can delete the step and connect the condition to the previous step or you can add a new step here.', 'wpfnl' ),
			'are_you_sure_to_delete_step'                 => __( 'Are you sure to delete this step?', 'wpfnl' ),
			'cancel'                                      => __( 'Cancel', 'wpfnl' ),
			'no'                                          => __( 'No', 'wpfnl' ),
			'connect_to_previous_step'                    => __( 'Connect to previous step', 'wpfnl' ),
			'add_new_step'                                => __( 'Add a new step', 'wpfnl' ),
			'yes'                                         => __( 'Yes', 'wpfnl' ),
			'are_you_sure_delete_product'                 => __( 'Are you sure you want to delete this product?', 'wpfnl' ),
			'thankyou'                                    => __( 'Thank you', 'wpfnl' ),
			'add_condition'                               => __( 'Add Condition', 'wpfnl' ),
			'add_featured_image'                          => __( 'Add Featured Image', 'wpfnl' ),
			'title'                                       => __( 'Title', 'wpfnl' ),
			'url_slug'                                    => __( 'URL Slug', 'wpfnl' ),
			'click_to_copy'                               => __( 'Click to Copy', 'wpfnl' ),
			'copied'                                      => __( 'Copied!', 'wpfnl' ),
			'update'                                      => __( 'Update', 'wpfnl' ),
			'enable_condition'                            => __( 'Enable Condition', 'wpfnl' ),
			'successfully_saved'                          => __( 'Succesfully saved', 'wpfnl' ),
			'no_condition_found'                          => __( 'No condition found', 'wpfnl' ),
			'add_new_condition'                           => __( 'Add New Condition', 'wpfnl' ),
			'and'                                         => __( 'And', 'wpfnl' ),
			'or'                                          => __( 'Or', 'wpfnl' ),
			'choose_next_steps'                           => __( 'Choose next steps', 'wpfnl' ),
			'false'                                       => __( 'False', 'wpfnl' ),
			'please_select'                               => __( 'Please select', 'wpfnl' ),
			'select_steps'                                => __( 'Select Steps', 'wpfnl' ),
			'select_step'                                 => __( 'Select Step', 'wpfnl' ),
			'add_step'                                    => __( 'Add Step', 'wpfnl' ),
			'no_steps_found'                              => __( 'No steps found', 'wpfnl' ),
			'reconfigure_conditions'                      => __( 'Reconfigure Conditions', 'wpfnl' ),
			'reconfigure_conditions_helper'               => __( 'Seems like you\'ve set a condition for the step earlier. Would you like to reconfigure?', 'wpfnl' ),
			'no_keep_the_same'                            => __( 'No, keep the same', 'wpfnl' ),
			'yes_reconfigure'                             => __( 'Yes, reconfigure', 'wpfnl' ),
			'zoom_in'                                     => __( 'Zoom In', 'wpfnl' ),
			'zoom_level'                                  => __( 'Zoom Level', 'wpfnl' ),
			'zoom_out'                                    => __( 'Zoom Out', 'wpfnl' ),

			// ------analytics------
			'weekly'                                      => __( 'Weekly', 'wpfnl' ),
			'monthly'                                     => __( 'Monthly', 'wpfnl' ),
			'yearly'                                      => __( 'Yearly', 'wpfnl' ),
			'custom'                                      => __( 'Custom', 'wpfnl' ),
			'start_date'                                  => __( 'Start Date', 'wpfnl' ),
			'end_date'                                    => __( 'End Date', 'wpfnl' ),
			'filter'                                      => __( 'Filter', 'wpfnl' ),
			'conversion_overview'                         => __( 'Conversion Overview', 'wpfnl' ),
			'previous'                                    => __( 'Previous', 'wpfnl' ),
			'next'                                        => __( 'Next', 'wpfnl' ),
			'total_visitors'                              => __( 'Total visitors:', 'wpfnl' ),
			'unique_visitors'                             => __( 'unique visitors:', 'wpfnl' ),
			'conversion_rates'                            => __( 'conversion rates:', 'wpfnl' ),
			'bounce_rates'                                => __( 'Bounce Rates:', 'wpfnl' ),
			'no_data_found'                               => __( 'No data Found', 'wpfnl' ),
			'from'                                        => __( 'From', 'wpfnl' ),
			'to'                                          => __( 'To', 'wpfnl' ),
			'revenue'                                     => __( 'Revenue', 'wpfnl' ),
			'are_you_sure_delete_analytics'               => __( 'Are you sure to delete all analytics data? This won\'t affect other funnels.', 'wpfnl' ),

			// ---webhook---
			'webhooks'                                    => __( 'Webhooks', 'wpfnl' ),
			'search_by_name'                              => __( 'Search by Name', 'wpfnl' ),
			'add_webhook'                                 => __( 'Add Webhook', 'wpfnl' ),
			'name'                                        => __( 'Name', 'wpfnl' ),
			'request_url'                                 => __( 'Request URL', 'wpfnl' ),
			'status'                                      => __( 'Status', 'wpfnl' ),
			'actions'                                     => __( 'Actions', 'wpfnl' ),
			'field_required'                              => __( 'Field Required', 'wpfnl' ),
			'key'                                         => __( 'Key', 'wpfnl' ),
			'value'                                       => __( 'Value', 'wpfnl' ),
			'select_value'                                => __( 'Select value', 'wpfnl' ),
			'select_name'                                 => __( 'Select name', 'wpfnl' ),
			'add'                                         => __( 'Add', 'wpfnl' ),
			'remove'                                      => __( 'Remove', 'wpfnl' ),
			'delete_webhook'                              => __( 'Delete Webhook', 'wpfnl' ),
			'want_to_delete_webhook_list'                 => __( 'Are you sure you want to delete this List?', 'wpfnl' ),
			'edit_field'                                  => __( 'Edit Field', 'wpfnl' ),
			'delete_field'                                => __( 'Delete Field', 'wpfnl' ),

			// ----webhook settings------
			'give_name_or_id'                             => __( 'Give a name or ID to this webhook.', 'wpfnl' ),
			'allow_connect_3rd_party_services'            => __( 'Allow to connect with 3rd party services.', 'wpfnl' ),
			'request_method'                              => __( 'Request Method', 'wpfnl' ),
			'select_http_request_method'                  => __( 'Select HTTP request sending method.', 'wpfnl' ),
			'select_method'                               => __( 'Select method', 'wpfnl' ),
			'request_format'                              => __( 'Request Format', 'wpfnl' ),
			'select_http_request_formate'                 => __( 'Select HTTP request sending format.', 'wpfnl' ),
			'select_format'                               => __( 'Select format', 'wpfnl' ),
			'webhook_events'                              => __( 'Webhook Events', 'wpfnl' ),
			'webhook_events_tooltip'                      => __( 'Select the WPFunnels event when the request would be sent.', 'wpfnl' ),
			'select_an_event'                             => __( 'Select an event', 'wpfnl' ),
			'request_body'                                => __( 'Request Body', 'wpfnl' ),
			'request_body_tooltip'                        => __( 'Select which values would be sent.', 'wpfnl' ),
			'all_fields'                                  => __( 'All Fields', 'wpfnl' ),
			'select_fields'                               => __( 'Select Fields', 'wpfnl' ),
			'select_fields'                               => __( 'Add New Condition', 'wpfnl' ),
			'new_condition'                               => __( 'New Condition', 'wpfnl' ),
			'saved_successfully'                          => __( 'Saved Successfully', 'wpfnl' ),
			'please_select_an_event_first'                => __( 'Please select an event first', 'wpfnl' ),

			// ----funnels settings drawer.vue------
			'funnel_settings'                             => __( 'Funnel Settings', 'wpfnl' ),
			'utm_settings'                                => __( 'UTM Settings', 'wpfnl' ),
			'guide_on_utm_integration'                    => __( 'Guide On UTM Integration', 'wpfnl' ),
			'enable_utm_parameters_for_this_funnel'       => __( 'Enable UTM Parameters For This Funnel', 'wpfnl' ),
			'pro'                                         => __( 'pro', 'wpfnl' ),
			'referrer_utm_source'                         => __( 'Referrer (utm_source)', 'wpfnl' ),
			'medium_utm_medium'                           => __( 'Medium (utm_medium)', 'wpfnl' ),
			'campaign_utm_campaign'                       => __( 'Campaign (utm_campaign)', 'wpfnl' ),
			'content_utm_content'                         => __( 'Content (utm_content)', 'wpfnl' ),
			'event_tracking'                              => __( 'Event Tracking', 'wpfnl' ),
			'exclude_this_funnel_from_fb_pixel_tracking'  => __( 'Exclude this funnel from FB Pixel tracking', 'wpfnl' ),
			'fb_pixel_tracking_hints'                     => __( 'Please connect from WPFunnels > Settings > Event Tracking to use it in this funnel.', 'wpfnl' ),
			'exclude_this_funnel_from_gtm_tracking'       => __( 'Exclude this funnel from GTM tracking', 'wpfnl' ),
			'gtm_tracking_hints'                          => __( 'Please connect from WPFunnels > Settings > Event Tracking to use it in this funnel.', 'wpfnl' ),
			'more_settings'                               => __( 'More Settings', 'wpfnl' ),
			'skip_offers_for_identical_products_in_the_cart' => __( 'Skip offers for identical products in the cart', 'wpfnl' ),
			'skip_offers_cart_tooltip'                    => __( 'Enable this if you want to skip upsell or downsell offers (for Global Funnels only) when the offer product is the same as main product in the cart.', 'wpfnl' ),
			'dont_skip_offer_for_higher_quantity'         => __( 'Don\'t skip if the offer is for a higher quantity', 'wpfnl' ),
			'dont_skip_offer_for_higher_quantity_tooltip' => __( 'Enabling this will mean that if the offer (for Global Funnels only) product is same as the product in the cart but with higher quantity, then it will not be skipped.', 'wpfnl' ),
			'skip_upsell_downsell_for_recurring_buyers'   => __( 'Skip upsell/downsell for recurring buyers', 'wpfnl' ),
			'skip_upsell_downsell_for_recurring_buyers_tooltip' => __( 'Enabling this will mean if the upsell/downsell offer product was purchased by a buyer in the last 365 days, the offer will be skipped.', 'wpfnl' ),
			'threshold_max_365'                           => __( 'Threshold (max 365)', 'wpfnl' ),
			'threshold_max_365_tooltip'                   => __( 'You may specify the number of days (less than 365 days) within which the product has to be purchased for the offer to be skipped. For example, skip the offer if the product was purchased within the last 100 days.', 'wpfnl' ),
			'lms_sales_sunnel_support'                    => __( 'LMS Sales Funnel Support', 'wpfnl' ),
			'use_this_funnel_for_lms_only'                => __( 'Use this funnel for LMS only', 'wpfnl' ),
			'use_this_funnel_for_lms_only_tooltip'        => __( 'Enable this option to turn this funnel into a sales funnel for LMS. You can use it when you have a supported LMS activated such as LearnDash.', 'wpfnl' ),
			'save_changes'                                => __( 'Save Changes', 'wpfnl' ),

			// ----AB testing------
			'running'                                     => __( 'Running', 'wpfnl' ),
			'draft'                                       => __( 'Draft', 'wpfnl' ),
			'archived_variant'                            => __( 'Archived Variant', 'wpfnl' ),
			'ab_test'                                     => __( 'A/B Test', 'wpfnl' ),
			'click_to_copy_ab_url'                        => __( 'Click to Copy A/B URL', 'wpfnl' ),
			'stats'                                       => __( 'Stats', 'wpfnl' ),
			'traffic_distribution'                        => __( 'Traffic Distribution', 'wpfnl' ),
			'start'                                       => __( 'Start', 'wpfnl' ),
			'pause'                                       => __( 'Pause', 'wpfnl' ),
			'archive_list'                                => __( 'Archive List', 'wpfnl' ),
			'templates'                                   => __( 'Templates', 'wpfnl' ),
			'duplicate'                                   => __( 'Duplicate', 'wpfnl' ),
			'duplicating'                                 => __( 'Duplicating..', 'wpfnl' ),
			'delete_variant'                              => __( 'Delete Variant', 'wpfnl' ),
			'are_you_sure_to_delete_this_variant'         => __( 'Are you sure to delete this Variant?', 'wpfnl' ),
			'visits'                                      => __( 'Visits', 'wpfnl' ),
			'conversion_rate'                             => __( 'Conversion Rate', 'wpfnl' ),
			'action'                                      => __( 'Action', 'wpfnl' ),
			'retrieve'                                    => __( 'Retrieve', 'wpfnl' ),
			'no_variation_found'                          => __( 'No Variation Found', 'wpfnl' ),
			'confirm_retrieve'                            => __( 'Confirm Retrieve', 'wpfnl' ),
			'are_you_sure_to_retrieve'                    => __( 'Are you sure to retrieve?', 'wpfnl' ),
			'confirm'                                     => __( 'Confirm', 'wpfnl' ),
			'retrieving'                                  => __( 'Retrieving...', 'wpfnl' ),
			'delete_this_variant'                         => __( 'Delete this Variant', 'wpfnl' ),
			'landing'                                     => __( 'Landing', 'wpfnl' ),
			'checkout'                                    => __( 'Checkout', 'wpfnl' ),
			'buy_now'                                     => __( 'Buy Now', 'wpfnl' ),
			'upsell'                                      => __( 'Upsell', 'wpfnl' ),
			'downsell'                                    => __( 'Downsell', 'wpfnl' ),
			'conversion'                                  => __( 'Conversion', 'wpfnl' ),
			'edit'                                        => __( 'Edit', 'wpfnl' ),
			'view_step'                                   => __( 'View Step', 'wpfnl' ),
			'more_option'                                 => __( 'More Option', 'wpfnl' ),
			'declare_as_winner'                           => __( 'Declare as Winner', 'wpfnl' ),
			'make_archive'                                => __( 'Make Archive', 'wpfnl' ),
			'archiving'                                   => __( 'Archiving...', 'wpfnl' ),
			'make_archive'                                => __( 'Make Archive', 'wpfnl' ),
			'traffic_distribution_tooltip'                => __( 'Traffic distribution should be 100%', 'wpfnl' ),
			'close'                                       => __( 'Close', 'wpfnl' ),
			'unlock_to_enter_value'                       => __( 'Unlock to Enter Value', 'wpfnl' ),
			'want_to_set_original'                        => __( 'Want to set “Original', 'wpfnl' ),
			'page_as_winner'                              => __( 'page” as a winner', 'wpfnl' ),
			'want_to_set'                                 => __( 'Want to set', 'wpfnl' ),
			'as_a_winner'                                 => __( 'as a winner', 'wpfnl' ),
			'set_variation_as_winner'                     => __( 'If you set this variation as a winner', 'wpfnl' ),
			'winner_check_list1'                          => __( 'Winner variant will be live on your site', 'wpfnl' ),
			'winner_check_list2'                          => __( 'Other variants will no longer available for the site visitors', 'wpfnl' ),
			'winner_check_list3'                          => __( 'Other variants will be on draft', 'wpfnl' ),
			'archive_all_except_winner'                   => __( 'Archive All Variants Except Winner', 'wpfnl' ),
			'declare_a_winner'                            => __( 'Declare a Winner', 'wpfnl' ),

			// -----mail mint automation-----
			'automation'                                  => __( 'Automation', 'wpfnl' ),
			'select_start_point'                          => __( 'Select A Start Point', 'wpfnl' ),
			'remove_this_trigger'                         => __( 'Remove this Trigger', 'wpfnl' ),
			'send_an_email'                               => __( 'Send An Email', 'wpfnl' ),
			'email_sequence'                              => __( 'Email Sequence', 'wpfnl' ),
			'assign_tags'                                 => __( 'Assign Tag(s)', 'wpfnl' ),
			'add_to_lists'                                => __( 'Add To List(s)', 'wpfnl' ),
			'remove_tags'                                 => __( 'Remove Tag(s)', 'wpfnl' ),
			'remove_lists'                                => __( 'Remove List(s)', 'wpfnl' ),
			'create_contact'                              => __( 'Create Contact', 'wpfnl' ),
			'outgoing_webhook'                            => __( 'Outgoing Webhook', 'wpfnl' ),
			'stop_automation'                             => __( 'Stop Automation', 'wpfnl' ),
			'delay'                                       => __( 'Delay', 'wpfnl' ),
			'twilioSendMessage'                           => __( 'Send Message', 'wpfnl' ),
			'if_else'                                     => __( 'If/Else', 'wpfnl' ),
			'set_hours_or_days'                           => __( 'Set a number of hours or days to wait before the next step of the journey is triggered.', 'wpfnl' ),
			'prepare_custom_email_template'               => __( 'Prepare a custom email template to send to contacts that enter this automation flow.', 'wpfnl' ),
			'choose_an_email_sequence'                    => __( 'Choose an email sequence to send at this point of your automation flow.', 'wpfnl' ),
			'choose_tags_to_assign'                       => __( 'Choose Tag(s) to assign to contacts.', 'wpfnl' ),
			'choose_list_to_assign'                       => __( 'Choose List(s) to add contacts on.', 'wpfnl' ),
			'choose_tag_to_remove'                        => __( 'Choose certain tag(s) to remove from your contacts.', 'wpfnl' ),
			'choose_list_to_remove'                       => __( 'Remove contacts from selected lists(s).', 'wpfnl' ),
			'contact_meets_if_else'                       => __( 'Contact meets If/Else conditions.', 'wpfnl' ),
			'create_user_hints'                           => __( 'If the contact is not on the contacts list already, a new contact will be created.', 'wpfnl' ),
			'stop_automation_hints'                       => __( 'Use this action to stop your automation workflow at this point.', 'wpfnl' ),
			'webhook_outgoing_hints'                      => __( 'Send Data to any external server via GET or POST Methods at any point of the Automation workflow', 'wpfnl' ),
			'twillo_hints'                      		  => __( 'Send a message to the contact.', 'wpfnl' ),
			'not_set_up_yet'                              => __( 'Not set up yet', 'wpfnl' ),
			'assigned_tags'                               => __( 'Assigned Tags', 'wpfnl' ),
			'removed_tags'                                => __( 'Removed Tags', 'wpfnl' ),
			'assigned_lists'                              => __( 'Assigned Lists', 'wpfnl' ),
			'removed_lists'                               => __( 'Removed Lists', 'wpfnl' ),
			'select_status'                               => __( 'Select Status :', 'wpfnl' ),
			'welcome_email_from_wpfunnels'                => __( 'Welcome email from WPFunnels', 'wpfnl' ),
			'stop_this_automation_workflow'               => __( 'Stop This Automation Workflow', 'wpfnl' ),
			'send_data_via_get_post_method'               => __( 'Send Data to external server via GET or POST Method', 'wpfnl' ),
			'delay_not_set_yet'                           => __( 'Delay Not Set Yet', 'wpfnl' ),
			'twilloBodyText'                           	  => __( 'Dispatch text message through Twilio SMS marketing.', 'wpfnl' ),
			'wait_for'                                    => __( 'Wait for', 'wpfnl' ),
			'delete_this_step'                            => __( 'Delete this Step', 'wpfnl' ),
			'exit'                                        => __( 'Exit', 'wpfnl' ),
			'choose_templates'                            => __( 'Choose Templates', 'wpfnl' ),
			'ready_email_templates'                       => __( 'Ready Email Templates', 'wpfnl' ),
			'my_saved_templates'                          => __( 'My Saved Templates', 'wpfnl' ),
			'edit_template'                               => __( 'Edit Template', 'wpfnl' ),
			'import'                                      => __( 'Import', 'wpfnl' ),
			'do_you_want_to_save_automation'              => __( 'Do you want to save the automation?', 'wpfnl' ),
			'no_back'                                     => __( 'No & Back', 'wpfnl' ),
			'yes_back'                                    => __( 'Yes & Back', 'wpfnl' ),
			'saving'                                      => __( 'Saving...', 'wpfnl' ),

			'choose_list'                                 => __( 'Choose List', 'wpfnl' ),
			'select_lists'                                => __( 'Select Lists', 'wpfnl' ),
			'search_list'                                 => __( 'Search List', 'wpfnl' ),
			'add_list'                                    => __( 'Add List', 'wpfnl' ),
			'choose_tags'                                 => __( 'Choose Tags', 'wpfnl' ),
			'select_tags'                                 => __( 'Select Tags', 'wpfnl' ),
			'search_tag'                                  => __( 'Search Tag', 'wpfnl' ),
			'add_tag'                                     => __( 'Add Tag', 'wpfnl' ),
			'contact_status'                              => __( 'Contact Status', 'wpfnl' ),
			'select_status'                               => __( 'Select Status', 'wpfnl' ),
			'set_waiting_time'                            => __( 'Set waiting time', 'wpfnl' ),
			'select_time'                                 => __( 'Select Time', 'wpfnl' ),
			'minutes'                                     => __( 'Minutes', 'wpfnl' ),
			'hours'                                       => __( 'Hours', 'wpfnl' ),
			'days'                                        => __( 'Days', 'wpfnl' ),
			'weeks'                                       => __( 'Weeks', 'wpfnl' ),
			'month'                                       => __( 'Month', 'wpfnl' ),
			'year'                                        => __( 'Year', 'wpfnl' ),
			'sender_email'                                => __( 'Sender Email', 'wpfnl' ),
			'sender_name'                                 => __( 'Sender Name', 'wpfnl' ),
			'reply_email'                                 => __( 'Reply Email', 'wpfnl' ),
			'reply_name'                                  => __( 'Reply Name', 'wpfnl' ),
			'email_subject_line'                          => __( 'Email Subject Line', 'wpfnl' ),
			'email_preview_text'                          => __( 'Email Preview Text', 'wpfnl' ),
			'email_preview_placeholder'                   => __( 'Be Specific and concise to spark interest', 'wpfnl' ),
			'design'                                      => __( 'Design', 'wpfnl' ),
			'select_a_template'                           => __( 'Select a Template', 'wpfnl' ),
			'choose_a_sequence'                           => __( 'Choose a sequence', 'wpfnl' ),
			'data_send_method'                            => __( 'Data Send Method', 'wpfnl' ),
			'remote_url'                                  => __( 'Remote URL', 'wpfnl' ),
			'request_format'                              => __( 'Request Format', 'wpfnl' ),
			'request_body_data'                           => __( 'Request Body Data', 'wpfnl' ),
			'body_key'                                    => __( 'Body Key', 'wpfnl' ),
			'body_value'                                  => __( 'Body Value', 'wpfnl' ),
			'enter_key'                                   => __( 'Enter key', 'wpfnl' ),
			'add_new_item'                                => __( 'Add new item', 'wpfnl' ),
			'add_new'                                     => __( 'Add New', 'wpfnl' ),
			'request_header'                              => __( 'Request Header', 'wpfnl' ),
			'header_key'                                  => __( 'Header Key', 'wpfnl' ),
			'header_value'                                => __( 'Header Value', 'wpfnl' ),
			'new_rule'                                    => __( 'New Rule', 'wpfnl' ),
			'enter_a_value'                               => __( 'Enter a value', 'wpfnl' ),
			'select_date'                                 => __( 'Select Date', 'wpfnl' ),

			// -----step importer------
			'enter_step_name'                             => __( 'Enter A Step Name', 'wpfnl' ),
			'find_your_templates'                         => __( 'Find your templates', 'wpfnl' ),
			'all'                                         => __( 'all', 'wpfnl' ),
			'free'                                        => __( 'free', 'wpfnl' ),
			'premium'                                     => __( 'Premium', 'wpfnl' ),
			'freemium'                                    => __( 'Freemium', 'wpfnl' ),
			'custom'                                      => __( 'Custom', 'wpfnl' ),
			'pages'                                       => __( 'Pages', 'wpfnl' ),
			'start_from_scratch'                          => __( 'Start From Scratch', 'wpfnl' ),
			'oops_it_looks_like'                          => __( 'Oops! It looks like', 'wpfnl' ),
			'is_inactive'                                 => __( 'is inactive.', 'wpfnl' ),
			'it_seems_like_you_have_selected'             => __( 'It seems like you have selected', 'wpfnl' ),
			'preferred_page_builder'                      => __( 'as your preferred page builder, but you do not have', 'wpfnl' ),
			'activated_your_site'                         => __( 'activated on your site.', 'wpfnl' ),
			'you_see_we_create_funnel_templates_for_gutenberg' => __( 'You see, we create funnel templates for Gutenberg using', 'wpfnl' ),
			'please_install_and_activate'                 => __( 'Please install and activate', 'wpfnl' ),
			'to_import_funnel_page_templates_for_gutenberg' => __( 'to import funnel page templates for Gutenberg.', 'wpfnl' ),
			'click_here_to_install_and_activate'          => __( 'Click here to install & activate', 'wpfnl' ),
			'if_you_want_to_create_funnel_steps_without'  => __( 'If you want to create funnel steps without using', 'wpfnl' ),
			'then_dont_worry'                             => __( 'then don"t worry.', 'wpfnl' ),
			'create_from_scratch'                         => __( 'You can go ahead and create this step from scratch and design it with any page builder/editor. It will work just fine.', 'wpfnl' ),
			'selected_builder_inactive'                   => __( 'Oops! It looks like the page builder you selected is inactive.', 'wpfnl' ),
			'elementor_didnot_active'                     => __( 'as your preferred page builder, but you do not have the plugin activated on your site.', 'wpfnl' ),
			'elementor_template_import'                   => __( 'to import funnel templates.', 'wpfnl' ),
			'if_you_want_to_design_funnel_steps_without'  => __( 'If you want to design the funnel steps without using', 'wpfnl' ),
			'divi_notice_body1'                           => __( 'It seems like you have selected Divi builder as your preferred page builder, but you do not have the plugin activated on your site. Please install and activate Divi builder to import funnel templates.', 'wpfnl' ),
			'divi_notice_body2'                           => __( 'If you want to design the funnel steps without using Divi builder, then don"t worry.', 'wpfnl' ),

			'oxygen_notice_body1'                         => __( 'It seems like you have selected Oxygen builder as your preferred page builder, but you do not have the plugin Oxygen builder  activated on your site.', 'wpfnl' ),
			'oxygen_notice_body2'                         => __( 'Please install and activate Oxygen builder to import ready funnel templates.', 'wpfnl' ),
			'oxygen_notice_body3'                         => __( 'If you want to create & design funnel pages without using Oxygen builder, then don"t worry.', 'wpfnl' ),
			'oxygen_notice_body4'                         => __( 'You can go ahead and create funnels from scratch using any page builder/editor. It will work just fine.', 'wpfnl' ),

			'importing_step'                              => __( 'Importing Step', 'wpfnl' ),
			'preview'                                     => __( 'Preview', 'wpfnl' ),

			// -----integration-------
			'funnel_integration'                          => __( 'Funnel Integration', 'wpfnl' ),
			'add_new_integration'                         => __( 'Add New Integration', 'wpfnl' ),
			'delete_selected'                             => __( 'Delete Selected', 'wpfnl' ),
			'crm'                                         => __( 'CRM', 'wpfnl' ),
			'trigger'                                     => __( 'Trigger', 'wpfnl' ),
			'options'                                     => __( 'Options', 'wpfnl' ),
			'are_you_sure_delete_automation'              => __( 'Are you sure you want to delete this Automation?', 'wpfnl' ),

			'integration_name'                            => __( 'Integration Name:', 'wpfnl' ),
			'edit_name'                                   => __( 'Edit Name', 'wpfnl' ),
			'save_name'                                   => __( 'Save Name', 'wpfnl' ),
			'cancel_changes'                              => __( 'Cancel Changes', 'wpfnl' ),
			'connect_crm'                                 => __( 'Connect CRM', 'wpfnl' ),
			'select_crm'                                  => __( 'Select CRM', 'wpfnl' ),
			'see_documantation'                           => __( 'See Documantation', 'wpfnl' ),
			'test_zapier_trigger'                         => __( 'Test Zapier Trigger', 'wpfnl' ),
			'choose_crm'                                  => __( 'Choose A CRM To Start Setting Up Your Integration', 'wpfnl' ),
			'no_crm_notice'                               => __( 'It appears no CRMs or automation tools are connected or activated. Please activate or connect a supported automation tool to use this feature.', 'wpfnl' ),
			'are_you_sure_dummy_data'                     => __( 'Are you sure want to send dummy data?', 'wpfnl' ),
			'field_sent_successfully'                     => __( 'Field Sent Successfully', 'wpfnl' ),
			'automation_name_required'                    => __( 'Automation name is required', 'wpfnl' ),
			'when'                                        => __( 'When', 'wpfnl' ),
			'user_event'                                  => __( 'User Event', 'wpfnl' ),
			'select_event'                                => __( 'Select event', 'wpfnl' ),
			'then'                                        => __( 'Then', 'wpfnl' ),
			'add_to_audience'                             => __( 'Add To Audience', 'wpfnl' ),
			'add_to_group'                                => __( 'Add To Group', 'wpfnl' ),
			'add_to_static_list'                          => __( 'Add To Static List', 'wpfnl' ),
			'add_to_list'                                 => __( 'Add To List', 'wpfnl' ),
			'assign_tag'                                  => __( 'Assign Tag', 'wpfnl' ),
			'enter_zapier_webhook_url'                    => __( 'Enter Zapier Webhook URL', 'wpfnl' ),
			'enter_pabbly_webhook_url'                    => __( 'Enter Pabbly Webhook URL', 'wpfnl' ),
			'select_tag'                                  => __( 'Please select', 'wpfnl' ),

			// -----coupon.vue----
			'select_coupon'                               => __( 'Select Coupon', 'wpfnl' ),
			'search_for_coupon'                           => __( 'Search for coupon', 'wpfnl' ),

			// -----discount.vue----
			'discount_type'                               => __( 'Discount Type', 'wpfnl' ),
			'discount_type_tooltip'                       => __( 'Choose if you want to give a unique percentage/flat discount on your', 'wpfnl' ),
			'discount_type_tooltip2'                      => __( 'Choose if you want to give a unique percentage/flat discount on your offer product in the funnel.', 'wpfnl' ),
			'discount_type_tooltip3'                      => __( 'Choose if you want to give a unique percentage/flat discount on your main product.', 'wpfnl' ),
			'offer'                                       => __( 'offer.', 'wpfnl' ),
			'discount_apply_to'                           => __( 'Discount Apply To', 'wpfnl' ),
			'discount_apply_to_tooltip'                   => __( 'Choose if you want the discount to be applied on the Regular Price or the Sale Price of the', 'wpfnl' ),
			'discount_apply_to_tooltip2'                  => __( 'Chose if you want the discount to be applied on the Regular Price or the Sale Price of the offer product.', 'wpfnl' ),
			'discount_apply_to_tooltip3'                  => __( 'Choose if you want the discount to be applied on the Regular Price or the Sale Price of the main product.', 'wpfnl' ),
			'offer_product'                               => __( 'offer product.', 'wpfnl' ),
			'discount_value'                              => __( 'Discount Value', 'wpfnl' ),
			'discount_value_tooltip'                      => __( '(when discount type is percentage) Assign a suitable percentage discount.', 'wpfnl' ),
			'discount_value_tooltip2'                     => __( '(when discount type is flat amount) Choose a certain amount to waive off.', 'wpfnl' ),
			'original_price'                              => __( 'Original Price', 'wpfnl' ),
			'sale_price'                                  => __( 'Sale Price', 'wpfnl' ),
			'discounted_offer_price'                      => __( 'Discounted Offer Price', 'wpfnl' ),
			'discounted_offer_price_tooltip1'             => __( 'This will be the', 'wpfnl' ),
			'discounted_offer_price_tooltip2'             => __( 'offer price your buyer will pay.', 'wpfnl' ),
			'discounted_offer_price_tooltip3'             => __( 'This will be the offer price your buyer will pay.', 'wpfnl' ),
			'discounted_offer_price_tooltip4'             => __( 'This will be the main product price your buyer will pay.', 'wpfnl' ),
			'replace_first_product'                       => __( 'Replace First Product', 'wpfnl' ),
			'replace_first_product_hints'                 => __( 'It will replace the first selected product (from checkout products) with the order bump product.', 'wpfnl' ),
			'replace_product'                             => __( 'Replace Product', 'wpfnl' ),
			'pre_purchase_upsell_text'                    => __( 'Set as pre-purchase upsell', 'wpfnl' ),
			'pre_purchase_upsell_tooltip_text'            => __( 'Enable this option to display a special offer to customers before they complete their purchase. The upsell will appear after they click the Place Order button, giving them a final opportunity to add the promoted product to their order.', 'wpfnl' ),
			'replace_product_tooltip'                     => __( 'Enabling this will mean that if a buyer accepts the order bump offer, then the order bump product will replace the main products in the checkout and the buyer will just have to pay for the order bump product.', 'wpfnl' ),
			'replace_product_hints'                       => __( 'It will replace all products (from checkout products) with the order bump product.', 'wpfnl' ),

			'search_for_categories'                       => __( 'Search for categories', 'wpfnl' ),
			'search_for_tag'                              => __( 'Search for tag', 'wpfnl' ),
			'product'                                     => __( 'Product', 'wpfnl' ),

			'enter_funnel_if'                             => __( 'Enter Funnel If', 'wpfnl' ),
			'add_category'                                => __( 'Add Category', 'wpfnl' ),
			'add_product'                                 => __( 'Add Product', 'wpfnl' ),
			'amount'                                      => __( 'Amount', 'wpfnl' ),
			'ammount_must_be_greater_than_1'              => __( 'Amount must be greater than 1', 'wpfnl' ),
			'ammount_must_be_greater_than_0'              => __( 'Amount must be greater than 0', 'wpfnl' ),
			'quantity'                                    => __( 'Quantity', 'wpfnl' ),
			'quantity_must_be_greater_than_1'             => __( 'Quantity must be greater than 1', 'wpfnl' ),
			'day_must_be_greater_than_or_euqal_1'         => __( 'Day must be greater than or euqal 1', 'wpfnl' ),
			'role'                                        => __( 'Role', 'wpfnl' ),
			'select_role'                                 => __( 'Select Role', 'wpfnl' ),
			'class'                                       => __( 'Class', 'wpfnl' ),
			'slect_shippin_class'                         => __( 'Select Shipping Class', 'wpfnl' ),
			'date'                                        => __( 'Date :', 'wpfnl' ),

			// -----image upload.vue------
			'product_image'                               => __( 'Product Image', 'wpfnl' ),
			'course_image'                                => __( 'Course Image', 'wpfnl' ),
			'product_image_tooltip'                       => __( 'You may change this image and use a custom image just for this order bump.', 'wpfnl' ),
			'click_to_change_image'                       => __( 'Click to Change Image', 'wpfnl' ),
			'click_to_update_image'                       => __( 'Drop an Image or ', 'wpfnl' ),
			'recommended_image_size'                      => __( 'Recommended image size 160px x 160px', 'wpfnl' ),

			// -----orderbump.vue------
			'order_bump'                                  => __( 'Order Bump', 'wpfnl' ),
			'add_order_bump'                              => __( 'Add Order Bump', 'wpfnl' ),
			'no_order_bump'                               => __( 'No Order Bump', 'wpfnl' ),
			'order_bump_hints1'                           => __( 'You can only add 1 order bump offer in the Free version.', 'wpfnl' ),
			'order_bump_hints2'                           => __( 'Upgrade to Pro now to create multiple order bump offers.', 'wpfnl' ),
			'upgrade_to_wpfunnels_pro'                    => __( 'Upgrade to WPFunnels Pro', 'wpfnl' ),
			'product_must_be_added'                       => __( 'Product must be added', 'wpfnl' ),
			'offer_must_be_selected'                      => __( 'Offer must be selected', 'wpfnl' ),
			'ob_default_product_name'                     => __( '6D Screen Protector (20% OFF)', 'wpfnl' ),
			'ob_highLight'                                => __( 'Special one time offer', 'wpfnl' ),
			'ob_checkbox_label'                           => __( 'Grab this offer with one click!', 'wpfnl' ),
			'ob_product_details'                          => __( 'Get this scratch proof 6D Tempered Glass Screen Protector for your iPhone. Keep your phone safe and sound just like a new one.', 'wpfnl' ),
			'ob_preview_tooltip'                          => __( 'The width of the order bump may vary depending on the page/container size on the Checkout Page or the device screen size.', 'wpfnl' ),
			'choose_offers'                               => __( 'Choose Offers', 'wpfnl' ),
			'choose_offers_condition'                     => __( 'Choose offer condition', 'wpfnl' ),
			'select_product'                              => __( 'Select Product', 'wpfnl' ),
			'select_product_tooltip'                      => __( 'Choose the product that you want to offer as order bump at the checkout.', 'wpfnl' ),
			'search_for_product'                          => __( 'Search for product', 'wpfnl' ),
			'search_for_course'                           => __( 'Search for course', 'wpfnl' ),
			'select_course'                               => __( 'Select Course', 'wpfnl' ),
			'choose_the'                                  => __( 'Choose the', 'wpfnl' ),
			'course'                                      => __( 'course', 'wpfnl' ),
			'select_course_tooltip'                       => __( 'that you want to offer as order bump at the checkout.', 'wpfnl' ),
			'course_title'                                => __( 'Course Title', 'wpfnl' ),
			'product_title'                               => __( 'Product Title', 'wpfnl' ),
			'select_color_for_title'                      => __( 'Select Color for Title', 'wpfnl' ),
			'course_quantity'                             => __( 'Course Quantity', 'wpfnl' ),
			'product_quantity'                            => __( 'Product Quantity', 'wpfnl' ),
			'highlight_text'                              => __( 'Highlight Text', 'wpfnl' ),
			'hilight_text_color'                          => __( 'Select Color for Highlight Text', 'wpfnl' ),
			'checkbox_label'                              => __( 'Checkbox Label', 'wpfnl' ),
			'checkbox_title_color'                        => __( 'Select Color for Checkbox Title', 'wpfnl' ),
			'course_description'                          => __( 'Course Description', 'wpfnl' ),
			'product_description'                         => __( 'Product Description', 'wpfnl' ),
			'product_description_color'                   => __( 'Select Color for Product Description', 'wpfnl' ),
			'ob_delete_confirmation'                      => __( 'Are you sure you want to Delete the', 'wpfnl' ),
			'enable_order_bump'                           => __( 'Enable Order Bump', 'wpfnl' ),
			'delete_order_bump'                           => __( 'Delete Order Bump', 'wpfnl' ),
			'ob_name'                                     => __( 'Order Bump Name', 'wpfnl' ),
			'select_template'                             => __( 'Select Template', 'wpfnl' ),
			'select_template_tooltip'                     => __( 'Choose a suitable template for your Order bump design.', 'wpfnl' ),
			'ob_position'                                 => __( 'Order Bump Position', 'wpfnl' ),
			'ob_position_tooltip'                         => __( 'Decide where the order bump offer should be placed in the checkout page.', 'wpfnl' ),
			'ob_arrow_color'                              => __( 'Order Bump Arrow Color', 'wpfnl' ),
			'ob_remove_arrow_color'                       => __( 'Remove arrow color', 'wpfnl' ),
			'ob_bg_color'                                 => __( 'Order Bump Background Color', 'wpfnl' ),
			'ob_bg_color_tooltip'                         => __( 'Change the color of the Checkbox bar if you want to.', 'wpfnl' ),
			'ob_remove_bg_color'                          => __( 'Remove background color', 'wpfnl' ),
			'ob_main_color'                               => __( 'Order Bump Main Color', 'wpfnl' ),
			'ob_main_color_tooltip'                       => __( 'Change the color of the Checkbox bar if you want to.', 'wpfnl' ),
			'ob_price_color'                              => __( 'Order bump price color', 'wpfnl' ),
			'price'                                       => __( 'Price', 'wpfnl' ),
			'regular_price'                               => __( 'Regular Price', 'wpfnl' ),
			'ob_template10_title'                         => __( 'to your order for only', 'wpfnl' ),
			'offer_price'                                 => __( 'Offer Price', 'wpfnl' ),

			// -----mint automation------
			'select_offer'                                => __( 'Select Offer', 'wpfnl' ),
			'select_order_bump'                           => __( 'Select Order Bump', 'wpfnl' ),

			// -----step body------
			'new_step'                                    => __( 'New Step', 'wpfnl' ),
			'new_step_hints'                              => __( 'Use this button to create the first step of the funnel.', 'wpfnl' ),
			'delete_this_step'                            => __( 'Delete this step', 'wpfnl' ),
			'ab_testing_running'                          => __( 'A/B Test Running', 'wpfnl' ),
			'view_automation'                             => __( 'View Automation', 'wpfnl' ),
			'add_automation_action'                       => __( 'Add Automation Action', 'wpfnl' ),
			'automation_running'                          => __( 'Automation running', 'wpfnl' ),
			'add_automation'                              => __( 'Add Automation', 'wpfnl' ),
			'upload_funnel_feature_image'                 => __( 'Uploade Funnel Featured Image', 'wpfnl' ),
			'oxygen_step_url'                             => __( 'Oxygen Builder Step URL', 'wpfnl' ),

			'no_product_added'                            => __( 'No Product Added', 'wpfnl' ),
			'no_course_added'                             => __( 'No Course Added', 'wpfnl' ),
			'run_ab_split_test'                           => __( 'Run A/B Split Test', 'wpfnl' ),
			'to_funnel_update_msg'                        => __( 'Please update to WPFunnels Pro 1.7.3 or higher to use this feature.', 'wpfnl' ),
			'conditions'                                  => __( 'Conditions', 'wpfnl' ),

			// -----step settings drawer------
			'assign_the_main_funnel_product'              => __( 'Assign the main funnel product here.', 'wpfnl' ),
			'courses'                                     => __( 'Courses', 'wpfnl' ),
			'products'                                    => __( 'Products', 'wpfnl' ),
			'set_ob_tooltip'                              => __( 'Set up an order bump offer at the checkout.', 'wpfnl' ),
			'edit_field_tooltip'                          => __( 'Checkout Field Editor for this funnel.', 'wpfnl' ),
			'edit_fields'                                 => __( 'Edit Fields', 'wpfnl' ),

			'add_new_field'                               => __( 'Add New Field', 'wpfnl' ),
			'select_section'                              => __( 'Select Section', 'wpfnl' ),
			'billing'                                     => __( 'Billing', 'wpfnl' ),
			'shipping'                                    => __( 'Shipping', 'wpfnl' ),
			'additional'                                  => __( 'Additional', 'wpfnl' ),
			'type'                                        => __( 'Type', 'wpfnl' ),
			'add_more'                                    => __( 'Add More', 'wpfnl' ),
			'option_text'                                 => __( 'Option text', 'wpfnl' ),
			'option_value'                                => __( 'Option value', 'wpfnl' ),
			'label'                                       => __( 'Label', 'wpfnl' ),
			'placeholder'                                 => __( 'Placeholder', 'wpfnl' ),
			'default_value'                               => __( 'Default Value', 'wpfnl' ),
			'validation'                                  => __( 'Validation', 'wpfnl' ),
			'validations'                                 => __( 'Validations', 'wpfnl' ),
			'required'                                    => __( 'Required', 'wpfnl' ),
			'enabled'                                     => __( 'Enabled', 'wpfnl' ),
			'display_in_order_detail'                     => __( 'Display in Order Detail Pages', 'wpfnl' ),
			'select_section_type'                         => __( 'Please select section type', 'wpfnl' ),
			'select_field_type'                           => __( 'Please select field type', 'wpfnl' ),
			'name_should_not_be_empty'                    => __( 'Name should not be empty', 'wpfnl' ),
			'label_should_not_be_empty'                   => __( 'Label should not be empty', 'wpfnl' ),
			'are_you_sure_delete_this_field'              => __( 'Are you sure you want to delete this field?', 'wpfnl' ),

			'billing_fields'                              => __( 'Billing Fields', 'wpfnl' ),
			'shipping_fields'                             => __( 'Shipping Fields', 'wpfnl' ),
			'additional_fields'                           => __( 'Additional Fields', 'wpfnl' ),
			'restore_to_default'                          => __( 'Restore to default', 'wpfnl' ),
			'add_field'                                   => __( 'Add Field', 'wpfnl' ),
			'additional_options'                          => __( 'Additional Options', 'wpfnl' ),
			'custom_order_button_text'                    => __( 'Custom order button text', 'wpfnl' ),
			'custom_order_button_text_tooltip'            => __( 'Set a custom text for the place order button.', 'wpfnl' ),
			'custom_billing_section_heading'              => __( 'Custom Billing Section Heading', 'wpfnl' ),
			'custom_billing_section_heading_tooltip'      => __( 'Set a custom heading text for the Billing Section.', 'wpfnl' ),
			'custom_shipping_section_heading'             => __( 'Custom Shipping Section Heading', 'wpfnl' ),
			'custom_shipping_section_heading_tooltip'     => __( 'Set a custom heading text for the Shipping Section.', 'wpfnl' ),
			'custom_order_detail_section_heading'         => __( 'Custom Order Detail Section Heading', 'wpfnl' ),
			'custom_order_detail_section_heading_tooltip' => __( 'Set a custom heading text for the Order Detail Section.', 'wpfnl' ),
			'save_additional_changes'                     => __( 'Save Addtional Changes', 'wpfnl' ),
			'billint_details'                             => __( 'Billing details', 'wpfnl' ),
			'shipto_different_address'                    => __( 'Ship to a different address?', 'wpfnl' ),
			'your_order'                                  => __( 'Your order', 'wpfnl' ),
			'place_order'                                 => __( 'Place Order', 'wpfnl' ),

			'this_product_in_trash'                       => __( 'This product is in the Trash. Please restore it', 'wpfnl' ),
			'recurring'                                   => __( 'Recurring', 'wpfnl' ),
			'subtext'                                     => __( 'Subtext', 'wpfnl' ),
			'enable_highlight'                            => __( 'Enable Highlight', 'wpfnl' ),
			'permanently_deleted'                         => __( 'This Product is permanently deleted', 'wpfnl' ),
			'edit_product'                                => __( 'Edit product', 'wpfnl' ),
			'delete_product'                              => __( 'Delete product', 'wpfnl' ),

			// -------product tab content----
			'funnel_products'                             => __( 'Funnel Products', 'wpfnl' ),
			'funnel_products_tooltip'                     => __( 'This will be the main offer product of the funnel. Search for a product, select it, and then click on the "+ Add Product" button.', 'wpfnl' ),
			'add_course'                                  => __( 'Add course', 'wpfnl' ),
			'total'                                       => __( 'Total', 'wpfnl' ),
			'allow_use_of_coupon'                         => __( 'Allow Use Of Coupon', 'wpfnl' ),
			'allow_use_of_coupon_tooltip'                 => __( 'Enable this if you want to allow buyers to use coupons during checkout in the funnel.', 'wpfnl' ),
			'coupon_auto_applied'                         => __( 'Coupon (auto-applied)', 'wpfnl' ),
			'coupon_auto_applied_tooltip'                 => __( 'Enable this to allow Auto-apply Coupon during the checkout on this funnel.', 'wpfnl' ),
			'quantity_limit'                              => __( 'Enable Quantity Limit', 'wpfnl' ),
			'quantity_limit_tooltip'                      => __( 'Enable this settings to set the quantity limit for a single user', 'wpfnl' ),
			'set_quantity_limit'                          => __( 'Set the quantity limit for a single user', 'wpfnl' ),
			'set_quantity_limit_tooltip'                  => __( 'Set the quantity limit for a single user', 'wpfnl' ),
			'choose_coupon'                               => __( 'Choose coupon', 'wpfnl' ),
			'choose_coupon_tooltip'                       => __( 'Choose any existing WooCommerce coupon from the dropdown.', 'wpfnl' ),
			'enable_quantity_selection'                   => __( 'Enable Quantity Selection', 'wpfnl' ),
			'enable_quantity_selection_tooltip'           => __( 'Enable this if you want to allow buyers to change the quantity of the product they want to buy.', 'wpfnl' ),

			// ------downsell-------
			'upsell_course'                               => __( 'Upsell Course', 'wpfnl' ),
			'downsell_course'                             => __( 'Downsell Course', 'wpfnl' ),
			'downsell_product'                            => __( 'Downsell Product', 'wpfnl' ),
			'select_an_offer'                             => __( 'Please select an offer', 'wpfnl' ),
			'select_category'                             => __( 'Select Category', 'wpfnl' ),
			'choose_offer_category'                       => __( 'Choose offer category', 'wpfnl' ),
			'choose_offer_tag'                            => __( 'Choose offer tag', 'wpfnl' ),
			'selected_price_is_less_than1'                => __( 'Selected price is less than 1', 'wpfnl' ),
			'gbf_product_quantity_tooltip'                => __( 'The value you input here will be added to the quantity of the product that triggered the funnel, which will be offered to the buyer as the downsell offer. For example, if someone purchases 2 t-shirts, and you input 3 here, the buyer will be offered 2+3 = 5 t-shirts as the downsell offer.', 'wpfnl' ),
			'selected_quantity_is_less_than1'             => __( 'Selected quantity is less than 1', 'wpfnl' ),
			'enable_replace_settings'                     => __( 'Enable Replace Settings', 'wpfnl' ),
			'enable_replace_settings_tooltip'             => __( 'Enabling this will mean that if a buyer accepts the downsell offer, then the downsell offer product will replace the main product in the checkout and the buyer will just have to pay for the downsell offer product.', 'wpfnl' ),
			'replace_main_order_including_order_bump'     => __( 'Replace Main Order Including Order Bump', 'wpfnl' ),
			'replace_main_order_excluding_order_bump'     => __( 'Replace Main Order Excluding Order bump', 'wpfnl' ),
			'replace_all_prior_orders'                    => __( 'Replace All Prior Orders', 'wpfnl' ),
			'replace_order_in_previous_step'              => __( 'Replace Order in Previous Step', 'wpfnl' ),
			'enable_replace_with_main_product'            => __( 'Enable replace with the main product.', 'wpfnl' ),
			'enable_replace_with_main_product_tooltip1'   => __( 'You must enable child orders for offers to use this feature.', 'wpfnl' ),
			'enable_replace_with_main_product_tooltip2'   => __( 'Go to WPFunnels > Settings > Offer Settings and enable "Create a new child order."', 'wpfnl' ),
			'product_must_be_added'                       => __( 'Product must be added', 'wpfnl' ),
			'offer_must_be_selected'                      => __( 'Offer must be selected', 'wpfnl' ),

			// ------thank you settings-------
			'show_order_overview'                         => __( 'Show Order Overview', 'wpfnl' ),
			'show_order_details'                          => __( 'Show Order Details', 'wpfnl' ),
			'show_billing_details'                        => __( 'Show Billing Details', 'wpfnl' ),
			'show_shipping_details'                       => __( 'Show Shipping Details', 'wpfnl' ),
			'thankyou_redicrection_page'                  => __( 'Thank You Page Redirection', 'wpfnl' ),
			'thankyou_redicrection_page_tooltip'          => __( 'Redirect buyers to an external link after viewing the thank you page for a few seconds or take the buyer to another page instead of the thank you page.', 'wpfnl' ),
			'redirect_url'                                => __( 'Redirect URL', 'wpfnl' ),
			'redirect_without_viewing_thankyou'           => __( 'Redirect without viewing Thank You Page', 'wpfnl' ),
			'redirect_without_viewing_thankyou_tooltip'   => __( 'The buyer will be taken to the redirect URL directly instead of the thank you page.', 'wpfnl' ),
			'redirect_after_viewing_thankyou'             => __( 'Redirect after viewing Thank You Page', 'wpfnl' ),
			'redirect_after_viewing_thankyou_tooltip'     => __( 'The buyer be taken to thank you page, and after the set time, will be redirected to the given redirect URL.', 'wpfnl' ),
			'redirect_after_in_second'                    => __( 'Redirect after [ in second ]', 'wpfnl' ),

			// -----upsell settings-----
			'upsell_product'                              => __( 'Upsell Product', 'wpfnl' ),
			'please_select_a_category'                    => __( 'Please select a category', 'wpfnl' ),
			'please_select_a_tag'                         => __( 'Please select a tag', 'wpfnl' ),
			'upsell_product_quantity_tooltip'             => __( 'The value you input here will be added to the quantity of the product that triggered the funnel, which will be offered to the buyer as the upsell offer. For example, if someone purchases 2 t-shirts, and you input 3 here, the buyer will be offered 2+3 = 5 t-shirts as the upsell offer.', 'wpfnl' ),
			'upsell_enable_replace_settings_tooltip'      => __( 'Enabling this will mean that if a buyer accepts the upsell offer, then the upsell offer product will replace the main product in the checkout and the buyer will just have to pay for the upsell offer product.', 'wpfnl' ),

			// -------funnels template-------
			'steps'                                       => __( 'Steps', 'wpfnl' ),
			'step'                                        => __( 'Step', 'wpfnl' ),
			'getting_ready_to_import'                     => __( 'Getting ready to import', 'wpfnl' ),
			'step_creating'                               => __( 'Creating Step...', 'wpfnl' ),
			'click_to_upgrade'                            => __( 'Click to Upgrade Pro', 'wpfnl' ),
			'template_builder_tooltip1'                   => __( 'as your preferred page builder, but you do not have the plugin activated on your site. Please install and activate', 'wpfnl' ),
			'if_you_want_to_create_funnels_without_using' => __( 'If you want to create funnels without using', 'wpfnl' ),
			'create_funnel_from_scratch'                  => __( 'You can go ahead and create funnels from scratch with any page builder/editor. It will work just fine.', 'wpfnl' ),
			'we_have_ready_templates_for'                 => __( 'As of now, we have ready templates for', 'wpfnl' ),
			'more_template_coming_soon'                   => __( 'only, but we are planning to create templates for more builders soon.', 'wpfnl' ),
			'create_a_funnel'                             => __( 'Create A Funnel', 'wpfnl' ),
			'how_to_use_this_funnel'                      => __( 'How to use this funnel', 'wpfnl' ),
			'creating_funnel'                             => __( 'Creating Funnel...', 'wpfnl' ),
			'write_funnel_name'                           => __( 'Write Funnel Name', 'wpfnl' ),
			'templates'                                   => __( 'Templates', 'wpfnl' ),
			'template'                                    => __( 'Template', 'wpfnl' ),
			'please_wait'                                 => __( 'Please Wait...', 'wpfnl' ),

			// -------Available payment gateways-------
			'disable_payment_gateways'                    => __( 'Disable payment gateways for this checkout', 'wpfnl' ),
			'disable_payment_gateways_tooltip'            => __( 'Disable payment gateways for this checkout to restrict payment options. This setting is useful for special cases where you want to manage payments manually or through alternative methods.', 'wpfnl' ),

			// -------Time bound discount-------
			'startDateText'                               => __( 'Start Date', 'wpfnl' ),
			'endDateText'                                 => __( 'End Date', 'wpfnl' ),
			'enableTimeBoundDiscountText'                 => __( 'Enable time bound discount', 'wpfnl' ),
			'enableTimeBoundDiscountTooltipText'          => __( 'This allows you to set a date range for offering discounts. Simply select the start and end dates, and the discount will automatically apply to purchases made during this period.', 'wpfnl' ),
			'undo'										  => __('Undo', 'wpfnl'),
			'stepCopied'								  => __('Step copied', 'wpfnl'),
			'copy'										  => __('Copy', 'wpfnl'),

			// -------Automation Analytics-------
			'report'									  => __('Report', 'wpfnl'),
			'back'										  => __('Back', 'wpfnl'),
			'automation'								  => __('Automation', 'wpfnl'),
			'performance'								  => __('Performance', 'wpfnl'),
			'open_rate'								  	  => __('Open Rate', 'wpfnl'),
			'click_rate'								  => __('Click Rate', 'wpfnl'),
			'email_sent'								  => __('Email Sent', 'wpfnl'),
			'open'										  => __('Open', 'wpfnl'),
			'click'										  => __('Click', 'wpfnl'),
			'overall_report'							  => __('Overall Report', 'wpfnl'),
			'entrance'							  		  => __('Entrance', 'wpfnl'),
			'subscribers_completed'						  => __('Subscribers Completed', 'wpfnl'),
			'welcome_to_wpfunnels'						  => __('Welcome to WPFunnels', 'wpfnl'),
			'setup_wizard'						  		  => __('Setup Wizard', 'wpfnl'),
			'support'						  		  	  => __('Support', 'wpfnl'),
			'video'						  		          => __('Video', 'wpfnl'),

			'total_customers'						  	  => __('Total Customers', 'wpfnl'),
			'total_orders'						  	  	  => __('Total Orders', 'wpfnl'),
			'total_revenue'						  	  	  => __('Total Revenue', 'wpfnl'),
			'order_bump_revenue'						  => __('Order Bump Revenue', 'wpfnl'),
			'upsell_downsell_evenue'					  => __('Upsell/Downsell Revenue', 'wpfnl'),
			'growth_comparison'					  		  => __('Growth Comparison', 'wpfnl'),
			'previous_year'					  		  	  => __('Previous Year', 'wpfnl'),
			'current_year'					  		  	  => __('Current Year', 'wpfnl'),
			'top_performing_funnels'					  => __('Top Performing Funnels', 'wpfnl'),
			'funnel_name'					  			  => __('Funnel Name', 'wpfnl'),
			'views'					  			  		  => __('Views', 'wpfnl'),
			'conversion'					  			  => __('Conversion', 'wpfnl'),
			'conversion_rate'					  		  => __('Conversion Rate', 'wpfnl'),
			'no_top_performing_funnels'					  => __('You have no top performing funnels', 'wpfnl'),
			'today'					  					  => __('Today', 'wpfnl'),
			'week_to_date'					  			  => __('Week to date', 'wpfnl'),
			'month_to_date'					  			  => __('Month to date', 'wpfnl'),
			'year_to_date'					  			  => __('Year to date', 'wpfnl'),
			'previous_period'					  		  => __('Previous period', 'wpfnl'),
			'previous_year'					  			  => __('Previous year', 'wpfnl'),

			// -------Funnel dashboard-------
		);
	}

	/**
	 * Maybe current theme is bricks
	 *
	 * @return bool
	 * @since 3.1.0
	 */
	public static function maybe_bricks_theme() {
		$current_theme = wp_get_theme();
		$parent_theme = $current_theme->parent();

		if ('Bricks' === $current_theme->get('Name')) {
			return true;
		}

		if ($parent_theme && 'Bricks' === $parent_theme->get('Name')) {
			return true;
		}

		return false;
	}


	/**
	 * Maybe current theme is WoodMart
	 *
	 * @return bool
	 * @since 3.1.0
	 */
	public static function maybe_woodmart_theme() {
		$current_theme = wp_get_theme();
		$parent_theme = $current_theme->parent();

		if ('Woodmart' === $current_theme->get('Name')) {
			return true;
		}

		if ($parent_theme && 'Woodmart' === $parent_theme->get('Name')) {
			return true;
		}

		return false;
	}


	/**
	 * Get WooCommerce price & currency configuration
	 *
	 * @return array
	 * @since 3.0.13
	 */
	public static function get_wc_price_config() {
	    $config = array(
            'currency_symbol'       => '$',
            'currency_position'     => 'left',
            'thousand_separator'    => ',',
            'decimal_separator'     => '.',
            'decimal_places'        => 2,
            'price_format'        	=> '%1$s%2$s',
        );

		if ( self::is_wc_active() ) {
			$config = array(
				'currency_symbol'    => get_woocommerce_currency_symbol(),
				'currency_position'  => get_option( 'woocommerce_currency_pos' ),
				'thousand_separator' => wc_get_price_thousand_separator(),
				'decimal_separator'  => wc_get_price_decimal_separator(),
				'decimal_places'     => wc_get_price_decimals(),
				'price_format'       => get_woocommerce_price_format(),
			);
		}
		return $config;
	}


	/**
	 * Get default meta for each steps
	 *
	 * @param $step_type
	 * @return array|array[]
	 *
	 * @since 3.1.0
	 */
	public static function get_step_default_meta( $step_type ) {
		$default_meta = array();
		switch ( $step_type ) {
			case 'landing':
				$default_meta = Wpfnl_Step_Meta_keys::get_landing_meta();
				break;
			case 'checkout':
				$default_meta = Wpfnl_Step_Meta_keys::get_checkout_meta();
				break;
			case 'thankyou':
				$default_meta = Wpfnl_Step_Meta_keys::get_thankyou_meta();
				break;
			case 'upsell':
			case 'downsell':
				$default_meta = Wpfnl_Step_Meta_keys::get_offer_meta();
				break;
			case 'custom':
				$default_meta = Wpfnl_Step_Meta_keys::get_custom_meta();
				break;
			default:
				break;
		}
		return $default_meta;
	}


	/**
	 * Get step type from step id
	 *
	 * @param $step_id
	 * @return mixed
	 * @since 3.1.0
	 */
	public static function get_step_type( $step_id ) {
		return get_post_meta( $step_id, '_step_type', true );
	}

	/**
	 * Check the current page is funnel page
	 *
	 * @return bool
	 * @since 3.1.4
	 */
	public static function maybe_funnel_page() {
		$supported_pages = array( 'wpfnl_settings', 'wpf-license', 'edit_funnel', 'wp_funnels' );
		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], $supported_pages ) ) {
			return true;
		}
		return false;
	}



	/**
	 * Retrieves the list of enabled payment gateways.
	 *
	 * @return array The list of enabled payment gateways.
	 *
	 * @since 3.1.8
	 */
	public static function get_enabled_payment_gateways() {
		if ( ! self::is_wc_active() ) {
			return array();
		}
		$gateways         = WC()->payment_gateways->get_available_payment_gateways();
		$enabled_gateways = array();
		if ( is_array( $gateways ) && ! empty( $gateways ) ) {
			foreach ( $gateways as $gateway ) {
				if ( $gateway->enabled == 'yes' ) {
					$enabled_gateways[ $gateway->id ] = $gateway->title;
				}
			}
		}
		return $enabled_gateways;
	}


	/**
	 * Check the slug is already available or not
	 *
	 * @param string $slug
	 * @param int    $post_id
	 * @param string $title
	 * @return bool
	 *
	 * @since 3.1.8
	 */
	public static function is_slug_available( $slug, $post_id = 0, $title = '' ) {
		global $wpdb;

		// If no title is provided, use the slug itself
		if ( empty( $title ) ) {
			$title = $slug;
		}

		// Check if the generated slug exists in the database
		$query  = $wpdb->prepare( "SELECT post_name FROM $wpdb->posts WHERE post_name = %s AND ID != %d", $slug, $post_id );
		$result = $wpdb->get_var( $query );

		// If the result is null, the slug is available
		if ( is_null( $result ) ) {
			return true;
		}

		return false;
	}


	// Function to get the total price of a composite product
	public static function get_composite_product_price( $composite_product_id, $use_regular_price = false ) {
		// Get the composite product object
		$composite_product = wc_get_product( $composite_product_id );

		// Check if the product is a composite product
		if ( ! $composite_product || 'composite' !== $composite_product->get_type() ) {
			return 'The product is not a composite product.';
		}

		// Initialize total price
		$total_price = $use_regular_price ? $composite_product->get_regular_price() : $composite_product->get_price();

		// Get components
		$components = $composite_product->get_components();

		// Loop through each component
		foreach ( $components as $component_id => $component ) {
			// Get the selected option (or default option if no selection)
			$selected_option = $component->get_default_option();

			// Check if component is optional and no selection made
			if ( $component->is_optional() && ! $selected_option ) {
				continue;
			}

			// Get the product object for the selected option
			$product       = wc_get_product( $selected_option );
			$configuration = array();
			// Calculate price based on selected option and quantity
			if ( $product ) {
				$component_quantity = $component->get_quantity( $configuration );
				$product_price      = $use_regular_price ? $product->get_regular_price() : $product->get_price();

				// Add the price of the selected option multiplied by the quantity
				$total_price += $product_price * $component_quantity;
			}
		}

		// Apply any composite product specific pricing rules or discounts
		// Note: This step may vary based on how your site applies discounts or custom pricing rules
		// $total_price = apply_composite_product_discounts($composite_product, $total_price);

		// Format and return the total price
		return $total_price;
	}


	public static function add_composite_product_to_cart_with_defaults( $composite_product_id, $quantity, $use_regular_price = false ) {

		// Get the composite product object
		$composite_product = wc_get_product( $composite_product_id );

		if ( ! defined( 'WC_CP_VERSION' ) ) {
			return $use_regular_price ? $composite_product->get_regular_price() : $composite_product->get_price();
		}

		// Check if the product is a composite product
		if ( ! $composite_product || 'composite' !== $composite_product->get_type() ) {
			return 'The product is not a composite product.';
		}

		// Prepare the components array for the cart
		$cart_item_data = array(
			'composite_data' => array(),
		);

		$components = $composite_product->get_components();

		foreach ( $components as $component_id => $component ) {
			// Get the default option for the component
			$selected_option_id = $component->get_default_option();

			if ( ! $selected_option_id && ! $component->is_optional() ) {
				return 'Missing required component option for component ID ' . $component_id;
			}

			// Get the product object for the selected option
			$product = wc_get_product( $selected_option_id );

			// Assuming configuration array as an empty array for this example
			// Ideally, this should contain the current state of the component configuration
			$configuration = array(); // You should replace this with actual configuration if available

			// Calculate quantity
			$component_quantity = $component->get_quantity( $configuration );

			// Add selected option and quantity to cart item data if the option is not null
			if ( $selected_option_id ) {
				$cart_item_data['composite_data'][ $component_id ] = array(
					'product_id' => $selected_option_id,
					'quantity'   => $component_quantity,
				);
			}
		}

		// Add the composite product to the cart
		$added = WC()->cart->add_to_cart( $composite_product_id, $quantity, 0, array(), $cart_item_data );

		if ( $added ) {
			return 'Composite product added to cart successfully.';
		} else {
			return 'Failed to add composite product to cart.';
		}
	}



	/**
	 * Checks if a theme is installed.
	 *
	 * @param string $theme_slug The slug of the theme to check.
	 * @return bool True if the theme is installed, false otherwise.
	 *
	 * @since 3.3.1
	 */
	public static function maybe_theme_installed( $theme_slug ) {
		$theme = wp_get_theme( $theme_slug );
		// Check if the theme exists and is not an empty theme
		if ( $theme->exists() ) {
			return true;
		} else {
			return false;
		}
	}


	/**
	 * Validate google autocomplete API key
	 *
	 * @param string $api_key
	 * @return bool
	 * @since 3.4.8
	 */
	public static function validate_google_places_api_key($api_key) {
		if (!is_string($api_key)) {
			// Throw an error or handle this case as appropriate for your application
			throw new InvalidArgumentException('API key must be a string');
		}

		$url = 'https://maps.googleapis.com/maps/api/place/textsearch/json?key=' . $api_key;

		$response = wp_remote_get($url);

		if (is_wp_error($response)) {
			return false;
		}

		$body = wp_remote_retrieve_body($response);
		$data = json_decode($body, true);

		if (isset($data['status']) && 'REQUEST_DENIED' === $data['status'] ) {
			return false;
		}

		return true;
	}
}

