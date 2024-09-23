<?php
/**
 * Condition checker
 * 
 * @package
 */
namespace WPFunnels\Conditions;

use WPFunnels\Traits\SingletonTrait;
use WPFunnels\Wpfnl_functions;
class Wpfnl_Condition_Checker {

	use SingletonTrait;


	/**
	 * Conditional node logic check
	 *
	 * @param $funnel_id
	 * @param $order
	 * @param $step_id
	 * @param $current_page_id
	 * @param $checker
	 * 
	 * @return bool
	 *
	 * @since 2.0.2
	 */
	public function check_condition( $funnel_id, $order = '', $step_id, $current_page_id = '', $checker = 'accept' )
	{
		if( !$step_id ){
			return false;
		}
		
		$group_conditions = Wpfnl_functions::get_conditions( $step_id );
		if( !is_array($group_conditions) ){
			return false;
		}

		// Loop through group condition.
		foreach ($group_conditions as $group) {

			if (empty($group)) {
				continue;
			}

			// Loop over rules and determine if all rules match.
			foreach ($group as $rule) {
				if (!$this->match_rule( $funnel_id ,$rule, $order, $current_page_id, $checker )) {
					return false;
				}
			}	 
		}
		return true;
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
	public function match_rule( $funnel_id , $rule, $order = '' , $current_page_id = '', $checker = 'accept' )
	{

		if ($rule['field'] == 'downsell') {
			$rule['field'] = 'upsell';
		}
		$condition = $rule['field'];
		
		if ( false !== strpos($rule['field'] , 'optin')){
			$condition = 'optin';
		}
		
		$checker_function = $condition . '_condition_checker';
		
		return self::$checker_function( $funnel_id ,$rule, $order, $current_page_id, $checker );
	}


	/**
	 * Optin condition checker for conditional step
     *
	 * @param Integer $funnel_id
	 * @param Array $data
	 * @param Object $order
	 * @param Integer $current_page_id
	 * @param String $checker
	 * 
	 * @return Boolean
	 * 
	 * @since 2.5.7
	 */
	public static function optin_condition_checker( $funnel_id, $rule, $data, $current_page_id, $checker = 'accept' ){

		if( is_array($data) && isset($data['step_id']) ){
			$step_id = str_replace('optin_','',$data['step_id'] );
			$optin_steps = get_option('optin_data');
			if( isset( $optin_steps['optin_data'] )){
				$key = array_search($step_id, array_column($optin_steps['optin_data'], 'step_id'));
				if( false !== $key ){
					unset($optin_steps[$key]);
					update_option('optin_data', $optin_steps );
					return true;
				}
			}
			
		}elseif( is_array($data) && !empty($data['cta']) ){
			return false;
		}
		
		if( isset($rule['field']) ){
			$step_id = str_replace('optin_','',$rule['field'] );
			$optin_steps = get_option('optin_data');
			if( isset( $optin_steps['optin_data'] )){
				$key = array_search($step_id, array_column($optin_steps['optin_data'], 'step_id'));
				if( false !== $key ){
					unset($optin_steps[$key]);
					update_option('optin_data', $optin_steps );
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
	public static function orderbump_condition_checker( $funnel_id, $data, $order, $current_page_id, $checker = 'accept' )
	{
		
		if ( Wpfnl_functions::is_wc_active() ) {
			$order_bump_accepted = WC()->session->get('order_bump_accepted');
			
			WC()->session->set('order_bump_accepted', null);
			$cookie_name        = 'wpfnl_order_bump';
			$cookie             = isset( $_COOKIE[$cookie_name] ) ? json_decode( wp_unslash( $_COOKIE[$cookie_name] ), true ) : array();
		
			if( isset( $cookie['order_bump_accepted'] ) ){
				$is_accepted = false;
				if( ( $data['value'] == $cookie['order_bump_accepted'] ) || ( 'no' === $data['value'] && '' === $cookie['order_bump_accepted'] )){
					$is_accepted = true;
				}
				ob_start();
				if( isset( $_COOKIE[$cookie_name] ) ){
					setcookie( $cookie_name, null, strtotime( '-1 days' ), '/', COOKIE_DOMAIN );
				}
				ob_end_flush();

				return $is_accepted;
			}else{
				if( 'no' === $data['value'] ){
					return true;
				}
			}
		}

		return false;
	}


	/**
	 * Cart total condition checker
	 * 
	 * @param $data
	 * @param $order
	 * @param $current_page_id
	 * 
	 * @return bool
	 */
	public static function carttotal_condition_checker( $funnel_id, $data, $order, $current_page_id, $checker = 'accept' )
	{
		$cart_total = $order->get_total();

		$checker = false;

		if( !isset( $data['condition'], $data['value'] ) ){
			return $checker;
		}

		if ( 'greater' === $data['condition'] ) {
			if ($cart_total > $data['value']) {
				$checker = true;
			}
		} elseif ( 'equal' === $data['condition'] ) {
			if ($cart_total == $data['value']) {
				$checker = true;
			}
		} elseif ( 'less' === $data['condition'] ) {
			if ($cart_total < $data['value']) {
				$checker = true;
			}
		}
		return $checker;
	}



	public static function upsell_condition_checker($funnel_id ,$data, $order, $current_page_id, $checker)
	{
		
		if( !isset( $data['value'] ) ){
			return false;
		}

		if ($data['value'] == 'yes') {
			if ( $checker == 'accept' ) {
				return true;
			} else {
				return false;
			}

		} else if ($data['value'] == 'no') {
			if ($checker == 'reject') {
				return true;
			} else {
				return false;
			}
		}
		return false;
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
	public static function billing_condition_checker( $funnel_id, $data, $order, $current_page_id, $checker ){
		
		if( !$order || !isset( $data['field'], $data['value'] ) ){
			return false;
		}

		$billing_country = $order->get_billing_country();
		if( 'billing' !== $data['field'] || $billing_country !== $data['value'] ){
			return false;
		}		
		return true;

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
	public static function shipping_condition_checker( $funnel_id, $data, $order, $current_page_id, $checker ){
		if( !$order || !isset( $data['field'], $data['value'] ) ){
			return false;
		}

		$shipping_country = $order->get_shipping_country();
		if( 'shipping' !== $data['field'] || $shipping_country !== $data['value'] ){
			return false;
		}

		return true;
	}





}
