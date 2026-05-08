<?php
/**
 * Order details
 * 
 * @package
 */
namespace WPFunnels\Widgets\Gutenberg\BlockTypes;

use WPFunnels\Data_Store\Wpfnl_Steps_Store_Data;

/**
 * Order setails class.
 */
class OrderDetails extends AbstractDynamicBlock {

    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'order-details';

	public function __construct( $block_name = '' )
	{
		parent::__construct($block_name);
		add_action('wp_ajax_show_order_details_markup', [$this, 'show_order_details_markup']);
	}

    /**
     * Render the Featured Product block.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block content.
     * 
     * @return string Rendered block type output.
     */
    protected function render( $attributes, $content ) {
		if( !isset($_GET['optin']) ) {
			// Get display condition settings
			$display_condition = isset($attributes['displayConditionType']) ? $attributes['displayConditionType'] : 'none';
			
			// Check display conditions and return early if block should be hidden
			if ($display_condition === 'user_state') {
				$hide_logged_in = isset($attributes['hideFromLoggedIn']) ? $attributes['hideFromLoggedIn'] : false;
				$hide_logged_out = isset($attributes['hideFromLoggedOut']) ? $attributes['hideFromLoggedOut'] : false;
				
				// Hide from logged in users
				if ($hide_logged_in && is_user_logged_in()) {
					return '';
				}
				
				// Hide from logged out users
				if ($hide_logged_out && !is_user_logged_in()) {
					return '';
				}

			} elseif ($display_condition === 'user_role') {
				$hide_for_user_role = isset($attributes['hideForUserRole']) ? $attributes['hideForUserRole'] : 'none';
				
				if ($hide_for_user_role !== 'none' && is_user_logged_in()) {
					$user = wp_get_current_user();
					if (in_array($hide_for_user_role, $user->roles)) {
						return '';
					}
				}

			} elseif ($display_condition === 'day') {
				$disable_on_days = isset($attributes['disableOnDays']) ? $attributes['disableOnDays'] : array();
				
				if (!empty($disable_on_days) && is_array($disable_on_days)) {
					$current_day = strtolower(date('l')); // e.g., 'monday'
					if (in_array($current_day, $disable_on_days)) {
						return '';
					}
				}

			} elseif ($display_condition === 'browser') {
				$hide_on_browser = isset($attributes['hideOnBrowser']) ? $attributes['hideOnBrowser'] : 'none';
				
				if ($hide_on_browser !== 'none') {
					$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
					$current_browser = '';
					
					// Detect browser from user agent
					if (strpos($user_agent, 'edg') !== false) {
						$current_browser = 'edge';
					} elseif (strpos($user_agent, 'opr') !== false || strpos($user_agent, 'opera') !== false) {
						$current_browser = 'opera_mini';
					} elseif (strpos($user_agent, 'chrome') !== false) {
						$current_browser = 'chrome';
					} elseif (strpos($user_agent, 'safari') !== false) {
						$current_browser = 'safari';
					} elseif (strpos($user_agent, 'firefox') !== false) {
						$current_browser = 'mozilla';
					}
					
					// Hide block if current browser matches
					if ($current_browser === $hide_on_browser) {
						return '';
					}
				}
			} elseif ($display_condition === 'operating_system') {
				$hide_on_os = isset($attributes['hideOnOS']) ? $attributes['hideOnOS'] : 'none';
				
				if ($hide_on_os !== 'none') {
					$user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : '';
					$current_os = '';
					
					// Detect operating system from user agent
					if (strpos($user_agent, 'windows') !== false || strpos($user_agent, 'win32') !== false || strpos($user_agent, 'win64') !== false) {
						$current_os = 'windows';
					} elseif (strpos($user_agent, 'macintosh') !== false || strpos($user_agent, 'mac os x') !== false) {
						$current_os = 'macos';
					} elseif (strpos($user_agent, 'linux') !== false && strpos($user_agent, 'android') === false) {
						$current_os = 'linux';
					} elseif (strpos($user_agent, 'android') !== false) {
						$current_os = 'android';
					} elseif (strpos($user_agent, 'iphone') !== false || strpos($user_agent, 'ipad') !== false || strpos($user_agent, 'ipod') !== false) {
						$current_os = 'ios';
					} elseif (strpos($user_agent, 'sunos') !== false) {
						$current_os = 'sunos';
					} elseif (strpos($user_agent, 'openbsd') !== false) {
						$current_os = 'openbsd';
					}
					
					// Hide block if current OS matches
					if ($current_os === $hide_on_os) {
						return '';
					}
				}
			}
			// -----end display conditioning----

			$output = sprintf('<div class="%1$s" style="%2$s">', esc_attr($this->get_classes($attributes)), esc_attr($this->get_styles($attributes)));
			$output .= '<div class="wpfnl-block-order-details__wrapper">';
			$output .= do_shortcode('[wpfunnels_order_details]');
			$output .= '</div>';
			$output .= '</div>';
			return $output;
		}
		return '';
    }


    /**
     * Get the styles for the wrapper element (background image, color).
     *
     * @param array       $attributes Block attributes. Default empty array.
     * 
     * @return string
     */
    public function get_styles( $attributes ) {
        $style      = '';
        return $style;
    }


    /**
     * Get class names for the block container.
     *
     * @param array $attributes Block attributes. Default empty array.
     * 
     * @return string
     */
    public function get_classes( $attributes ) {
		global $post;
		$thankyou = new Wpfnl_Steps_Store_Data();
		$thankyou->read($post->ID);
		$order_overview     = $thankyou->get_internal_metas_by_key('_wpfnl_thankyou_order_overview');
		$order_details      = $thankyou->get_internal_metas_by_key('_wpfnl_thankyou_order_details');
		$billing_details    = $thankyou->get_internal_metas_by_key('_wpfnl_thankyou_billing_details');
		$shipping_details   = $thankyou->get_internal_metas_by_key('_wpfnl_thankyou_shipping_details');

        $classes = array(
        	'wpfnl-block-' . $this->block_name,
        	'wpfnl-gutenberg-display-order-overview-' . $order_overview,
        	'wpfnl-gutenberg-display-order-details-' . $order_details,
        	'wpfnl-gutenberg-display-billing-address-' . $billing_details,
        	'wpfnl-gutenberg-display-shipping-address-' . $shipping_details,
		);

		// Add responsive classes
		if (isset($attributes['hideOnDesktop']) && $attributes['hideOnDesktop']) {
			$classes[] = 'wpfnl-hide-desktop';
		}
		if (isset($attributes['hideOnTablet']) && $attributes['hideOnTablet']) {
			$classes[] = 'wpfnl-hide-tablet';
		}
		if (isset($attributes['hideOnMobile']) && $attributes['hideOnMobile']) {
			$classes[] = 'wpfnl-hide-mobile';
		}
		
		// Add animation class
		if (isset($attributes['animation']) && !empty($attributes['animation'])) {
			$classes[] = 'wpfnl-animation';
			$classes[] = esc_attr($attributes['animation']);
		}

        return implode( ' ', $classes );
    }


    /**
     * Extra data passed through from server to client for block.
     *
     * @param array $attributes  Any attributes that currently are available from the block.
     *                           Note, this will be empty in the editor context when the block is
     *                           not in the post content on editor load.
     */
    protected function enqueue_data( array $attributes = [] ) {
        parent::enqueue_data( $attributes );
    }


	/**
	 * Render order details markup
	 *
	 * @return string
	 *
	 * @since 2.0.3
	 */
    public function show_order_details_markup() {

		add_filter('wpfunnels/show_dummy_order_details', function () {
			return true;
		});
		$data['html'] = do_shortcode( '[wpfunnels_order_details]' );
		wp_send_json_success($data);
		die();
	}

}
