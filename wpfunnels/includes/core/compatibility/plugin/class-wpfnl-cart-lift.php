<?php
/**
 * CartLift Compatibility
 * 
 * @package WPFunnels\Compatibility\Plugin
 */
namespace WPFunnels\Compatibility\Plugin;

use WPFunnels\Wpfnl_functions;
use WPFunnels\Traits\SingletonTrait;

/**
 * CartLift Compatibility
 * 
 * @package WPFunnels\Compatibility\CartLift
 */
class CartLift extends PluginCompatibility{
	use SingletonTrait;

	/**
	 * Filters/Hook from Cart Lift
	 * to initiate the necessary updates.
	 *
	 * @since 3.0.0
	 */
	public function init() {
		add_filter( 'cl_default_general_settings', [ $this, 'update_cl_general_settings' ] );
		add_action( 'cl_after_abandoned_cart_tracking_field', [ $this, 'render_funnel_global_option' ] );
		add_filter( 'cl_cart_tracking_status', [ $this, 'update_cl_cart_tracking_status' ] );
		add_filter( 'cl_cart_tracking_status_ajax', [ $this, 'update_cl_cart_tracking_status' ] );
		add_filter( 'cl_cart_details_before_update', [ $this, 'update_cl_cart_details' ], 10, 2 );
		add_filter( 'cl_cart_details_before_update_ajax', [ $this, 'update_cl_cart_details' ], 10, 2 );
		add_filter( 'cl_email_checkout_url', [ $this, 'get_cl_funnel_checkout_url' ], 10, 3 );
	}

	/**
	 * Add WPFunnels tracking option
	 * in Cart Lift global general settings
	 *
	 * @param $default_settings
	 * 
	 * @return mixed
	 * @since  3.0.0
	 */
	public function update_cl_general_settings( $default_settings ) {
		$default_settings[ 'wpfunnels_tracking' ] = 0;
		return $default_settings;
	}

	/**
	 * Render markups for WPFunnels
	 * global general settings option.
	 *
	 * @param $general_data
	 * 
	 * @since 3.0.0
	 */
	public function render_funnel_global_option( $general_data ) {
		$wpfunnels_tracking_status = 'no';
		$wpfunnels_tracking = isset( $general_data['wpfunnels_tracking'] ) ? $general_data['wpfunnels_tracking'] : 0;
		if($wpfunnels_tracking) {
			$wpfunnels_tracking_status = 'yes';
		}
		?>
		<div class="cl-form-group">
			<div class="cl-global-tooltip-area">
				<span class="title"><?php echo __( 'Enable abandoned cart tracking for WPFunnels:', 'wpfnl' ); ?></span>
				
				<div class="tooltip">
					<span class="icon">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <rect x="1" y="1.1925" width="14" height="14" rx="7" stroke="#535963"/>
                        <path d="M6 5.69543C6.0741 4.80132 6.85381 4.13315 7.74894 4.19668H8.24864C9.14377 4.13315 9.92347 4.80132 9.99758 5.69543C10.0354 6.36175 9.62793 6.97278 8.99818 7.19418C8.30536 7.60992 7.91577 8.38893 7.99879 9.1925" stroke="#535963" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M8.5 12.19V12.195" stroke="#535963" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
					<p><?php echo __( 'This will enable tracking abandoned cart from WPFunnels', 'wpfnl' ); ?></p>
				</div>

			</div>

			<span class="cl-switcher">
				<input class="cl-toggle-option" type="checkbox" id="wpfunnels_tracking" name="wpfunnels_tracking" data-status="<?php echo $wpfunnels_tracking_status; ?>" value="<?php echo $wpfunnels_tracking; ?>" <?php checked( '1', $wpfunnels_tracking ); ?>/>
				<label for="wpfunnels_tracking"></label>
			</span>
		</div>
		<?php
	}

	/**
	 * Update cart tracking status for WPFunnels
	 * while saving cart data into Cart Lift DB.
	 *
	 * @param $cart_track
	 * 
	 * @return mixed|string
	 * @since  3.0.0
	 */
	public function update_cl_cart_tracking_status( $cart_track ) {
		$post_id = get_the_ID();
		$post_type = get_post_type( $post_id );

		if( $post_id > 1  && $post_type === 'wpfunnel_steps') {
			return cl_get_general_settings_data( 'wpfunnels_tracking' );
		}
		elseif( !empty( $_POST ) && isset( $_POST['post_data'] ) ) {
			$post_data = array();
			if( !empty( $post_data ) && isset( $post_data['_wpfunnels_funnel_id'] ) ) {
				$funnel_id = $post_data['_wpfunnels_funnel_id'];
				$funnel_data = Wpfnl_functions::get_funnel_data( $funnel_id );
				if( $funnel_data !== '' ) {
					return cl_get_general_settings_data( 'wpfunnels_tracking' );
				}
			}
		}
		elseif( !empty( $_POST ) && isset( $_POST['wpfunnels_checkout_id'] ) ) {
			$wpfunnels_checkout_id = $_POST['wpfunnels_checkout_id'];
			$funnel_id = Wpfnl_functions::get_funnel_id_from_step( $wpfunnels_checkout_id );

			if( $funnel_id != 0 ) {
				return cl_get_general_settings_data( 'wpfunnels_tracking' );
			}
		}
		return $cart_track;
	}

	/**
	 * Add funnel id and step id from WPFunnels
	 * while saving cart data [in cart_meta] if the checkout
	 * is from any funnel checkout from WPFunnels.
	 *
	 * @param $cart_details
	 * @param $session_id
	 * 
	 * @return mixed
	 * @since  3.0.0
	 */
	public function update_cl_cart_details( $cart_details, $session_id ) {
		$post_id = get_the_ID();
		if( $post_id != 0 ) {
			$post_type = get_post_type( get_the_ID() );

			if( $post_type && $post_type === 'wpfunnel_steps' ) {
				$step_id = $post_id;
				$funnel_id = Wpfnl_functions::get_funnel_id_from_step($step_id);
				$cart_meta = array();
				if( isset( $cart_details['cart_meta'] ) ) {
					$cart_meta = unserialize( $cart_details['cart_meta'] );
				}
				$cart_meta['wpfunnel_id'] = $funnel_id;
				$cart_meta['wpfunnel_step_id'] = $step_id;
				$cart_details['cart_meta'] = serialize($cart_meta);
			}
		}
		return $cart_details;
	}

	/**
	 * Modify checkout url of checkout button in
	 * the campaign email for cart abandoned
	 * from any funnel checkout build from WPFunnels.
	 *
	 * @param $checkout_url
	 * @param $token
	 * @param $email_data
	 * 
	 * @return mixed|string
	 * @since  3.0.0
	 */
	public function get_cl_funnel_checkout_url( $checkout_url, $token, $email_data ) {
		if( !empty( $email_data ) && isset( $email_data->cart_meta ) ){
			$cart_meta = unserialize( $email_data->cart_meta );
			$funnel_id =  isset( $cart_meta['wpfunnel_id'] ) ? $cart_meta['wpfunnel_id'] : 0;
			$step_id   =  isset( $cart_meta['wpfunnels_checkout_id'] ) ? $cart_meta['wpfunnels_checkout_id'] : 0;

			if( $funnel_id != 0 ) {
				if( Wpfnl_functions::is_global_funnel( $funnel_id ) ) {
					return get_permalink( $step_id ) . '?cl_token='  . $token;
				}
				return get_permalink( $step_id ) . '?cl_token='  . 'wpfunnels_checkout';
			}
		}
		return $checkout_url;
	}


	/**
	 * Check if cart list is activated
	 *
	 * @return bool
	 * @since  2.7.7
	 */
	public function maybe_activate()
	{
		if (in_array('cart-lift/cart-lift.php', apply_filters('active_plugins', get_option('active_plugins')))) {
			return true;
		}elseif( function_exists('is_plugin_active') ){
			if( is_plugin_active( 'cart-lift/cart-lift.php' )){
				return true;
			}
		}
		return false;
	}
}
