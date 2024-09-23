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
			<span class="title"><?php echo __( 'Enable abandoned cart tracking for WPFunnels:', 'wpfnl' ); ?></span>
			<span class="cl-switcher">
				<input class="cl-toggle-option" type="checkbox" id="wpfunnels_tracking" name="wpfunnels_tracking" data-status="<?php echo $wpfunnels_tracking_status; ?>" value="<?php echo $wpfunnels_tracking; ?>" <?php checked( '1', $wpfunnels_tracking ); ?>/>
				<label for="wpfunnels_tracking"></label>
			</span>
			<div class="tooltip">
                <span class="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 19" width="18" height="19">
                        <defs>
                            <clipPath clipPathUnits="userSpaceOnUse" id="cp1">
                                <path d="M-941 -385L379 -385L379 866L-941 866Z" />
                            </clipPath>
                        </defs>
                        <style>
                            tspan { white-space:pre }
							.shp0 { fill: #6e42d3 }
                        </style>
                        <g id="Final Create New Abandoned Cart Campaign " clip-path="url(#cp1)">
                            <g id="name">
                                <g id="question">
                                    <path id="Shape" fill-rule="evenodd" class="shp0" d="M18 10C18 14.97 13.97 19 9 19C4.03 19 0 14.97 0 10C0 5.03 4.03 1 9 1C13.97 1 18 5.03 18 10ZM16.8 10C16.8 5.7 13.3 2.2 9 2.2C4.7 2.2 1.2 5.7 1.2 10C1.2 14.3 4.7 17.8 9 17.8C13.3 17.8 16.8 14.3 16.8 10Z" />
                                    <path id="Path" class="shp0" d="M8.71 11.69C8.25 11.69 7.87 12.07 7.87 12.53C7.87 12.98 8.24 13.37 8.71 13.37C9.19 13.37 9.56 12.98 9.56 12.53C9.56 12.07 9.18 11.69 8.71 11.69Z" />
                                    <path id="Path" class="shp0" d="M8.64 6.06C7.35 6.06 6.75 6.85 6.75 7.38C6.75 7.77 7.07 7.94 7.33 7.94C7.84 7.94 7.63 7.19 8.61 7.19C9.09 7.19 9.48 7.4 9.48 7.86C9.48 8.39 8.94 8.69 8.62 8.97C8.34 9.21 7.98 9.62 7.98 10.47C7.98 10.98 8.11 11.12 8.51 11.12C8.98 11.12 9.07 10.91 9.07 10.72C9.07 10.21 9.08 9.91 9.61 9.49C9.87 9.28 10.69 8.61 10.69 7.69C10.69 6.76 9.87 6.06 8.64 6.06Z" />
                                </g>
                            </g>
                        </g>
                    </svg>
                </span>
				<p><?php echo __( 'This will enable tracking abandoned cart from WPFunnels', 'wpfnl' ); ?></p>
			</div>
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
