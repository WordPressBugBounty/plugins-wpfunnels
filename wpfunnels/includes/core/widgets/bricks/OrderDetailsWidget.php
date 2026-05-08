<?php

/**
 * Namespace for the OrderDetailsWidget class.
 *
 * This namespace defines the location of the OrderDetailsWidget class within the WPFunnels\Widgets\Bricks namespace.
 */
namespace WPFunnels\Widgets\Bricks;

require_once get_template_directory() . '/includes/elements/base.php';

use \Bricks\Element;
use Error;

if (! defined('ABSPATH')) {
    exit;
} // Exit if accessed directly


/**
 * Class OrderDetailsWidget
 * 
 * Represents a widget for displaying order details.
 * 
 * @package WPFunnels\Widgets\Bricks
 */
class OrderDetailsWidget extends Element
{

    // Element properties
    public $category     = 'wpfunnels'; // Use predefined element category 'general'
    public $name         = 'wpfnl_order_deatils'; // Make sure to prefix your elements
    public $icon         = 'fa-solid fa-cart-shopping'; // Themify icon font class
    public $css_selector = '.wpfunnels-bricks-order-details-icon'; // Default CSS selector
    public $scripts      = []; // Script(s) run when element is rendered on frontend or updated in builder


     /**
     * Return localised element label
     * 
     * @return string
     * @since 3.1.0
     */
    public function get_label()
    {
        return esc_html__('Order Detail', 'wpfnl');
    }

    /**
     * Get WordPress user roles for display condition control.
     *
     * @return array
     * @since 3.1.0
     */
    protected function get_wp_user_roles()
    {
        $roles = [ 'none' => esc_html__( 'None', 'wpfnl' ) ];

        if ( ! function_exists( 'get_editable_roles' ) ) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }

        foreach ( get_editable_roles() as $role_key => $role_info ) {
            $roles[ $role_key ] = $role_info['name'];
        }

        return $roles;
    }

    
    /**
     * Set builder control groups
     * 
     * @since 3.1.0
     */
    public function set_control_groups()
    {
        $this->control_groups['wpfunnels'] = [
            'title' => esc_html__('Settings', 'wpfnl'), // Localized control group title
            'tab' => 'content', // Set to either "content" or "style"
        ];
    }

    /**
     * Set builder controls
     * 
     * @since 3.1.0
     */
    public function set_controls()
    {

        $wpfnl_thankyou_order_overview = get_post_meta($this->post_id, '_wpfnl_thankyou_order_overview', true);
        $wpfnl_thankyou_order_details = get_post_meta($this->post_id, '_wpfnl_thankyou_order_details', true);
        $wpfnl_thankyou_billing_details = get_post_meta($this->post_id, '_wpfnl_thankyou_billing_details', true);
        $wpfnl_thankyou_shipping_details = get_post_meta($this->post_id, '_wpfnl_thankyou_shipping_details', true);

        if (!$wpfnl_thankyou_order_overview) {
            $wpfnl_thankyou_order_overview = 'on';
        }
        if (!$wpfnl_thankyou_order_details) {
            $wpfnl_thankyou_order_details = 'on';
        }
        if (!$wpfnl_thankyou_billing_details) {
            $wpfnl_thankyou_billing_details = 'on';
        }
        if (!$wpfnl_thankyou_shipping_details) {
            $wpfnl_thankyou_shipping_details = 'on';
        }

       
        $this->controls['enable_order_review'] = [
            'tab' => 'content',
            'group' => 'wpfunnels',
            'label' => esc_html__('Show Order Overview', 'wpfnl'),
            'type' => 'select',
            'options' => [
                'on' => esc_html__( 'On', 'wpfnl' ),
                'off' => esc_html__( 'Off', 'wpfnl' ),
            ],
            'inline' => true,
            'clearable' => false,
            'pasteStyles' => false,
            'default' => $wpfnl_thankyou_order_overview,
        ];

        $this->controls['enable_order_details'] = [
            'tab' => 'content',
            'group' => 'wpfunnels',
            'label' => esc_html__('Show Order Details', 'wpfnl'),
            'type' => 'select',
            'options' => [
                'on' => esc_html__( 'On', 'wpfnl' ),
                'off' => esc_html__( 'Off', 'wpfnl' ),
            ],
            'inline' => true,
            'clearable' => false,
            'pasteStyles' => false,
            'default' => $wpfnl_thankyou_order_details,
        ];

        $this->controls['enable_billing_details'] = [
            'tab' => 'content',
            'group' => 'wpfunnels',
            'label' => esc_html__('Show Billing Details', 'wpfnl'),
            'type' => 'select',
            'options' => [
                'on' => esc_html__( 'On', 'wpfnl' ),
                'off' => esc_html__( 'Off', 'wpfnl' ),
            ],
            'inline' => true,
            'clearable' => false,
            'pasteStyles' => false,
            'default' => $wpfnl_thankyou_billing_details,
        ];

        $this->controls['enable_shipping_details'] = [
            'tab' => 'content',
            'group' => 'wpfunnels',
            'label' => esc_html__('Show Shipping Details', 'wpfnl'),
            'type' => 'select',
            'options' => [
                'on' => esc_html__( 'On', 'wpfnl' ),
                'off' => esc_html__( 'Off', 'wpfnl' ),
            ],
            'inline' => true,
            'clearable' => false,
            'pasteStyles' => false,
            'default' => $wpfnl_thankyou_shipping_details,
        ];

        // Responsive Display
        $this->controls['responsiveSeparator'] = [
            'tab'         => 'content',
            'group'       => 'wpfunnels',
            'label' => esc_html__( 'Responsive Display', 'wpfnl' ),
            'type'  => 'separator',
        ];

        $this->controls['hide_on_desktop'] = [
            'tab'         => 'content',
            'group'       => 'wpfunnels',
            'label'       => esc_html__( 'Hide on Desktop (≥ 1200px)', 'wpfnl' ),
            'type'        => 'checkbox',
            'default'     => false,
            'description' => esc_html__( 'Hide order details on desktop devices (window width >= 1200px)', 'wpfnl' ),
        ];

        $this->controls['hide_on_tablet'] = [
            'tab'         => 'content',
            'group'       => 'wpfunnels',
            'label'       => esc_html__( 'Hide on Tablet (768px – 1199px)', 'wpfnl' ),
            'type'        => 'checkbox',
            'default'     => false,
            'description' => esc_html__( 'Hide order details on tablet devices (window width 768px - 1199px)', 'wpfnl' ),
        ];

        $this->controls['hide_on_mobile'] = [
            'tab'         => 'content',
            'group'       => 'wpfunnels',
            'label'       => esc_html__( 'Hide on Mobile (≤ 767px)', 'wpfnl' ),
            'type'        => 'checkbox',
            'default'     => false,
            'description' => esc_html__( 'Hide order details on mobile devices (window width <= 767px)', 'wpfnl' ),
        ];

        // Display Conditions
        $this->controls['displayConditionSeparator'] = [
            'tab'         => 'content',
            'group'       => 'wpfunnels',
            'label' => esc_html__( 'Display Conditions', 'wpfnl' ),
            'type'  => 'separator',
        ];

        $this->controls['display_condition_type'] = [
            'tab'         => 'content',
            'group'       => 'wpfunnels',
            'label'       => esc_html__( 'Display Condition', 'wpfnl' ),
            'type'        => 'select',
            'options'     => [
                'none'             => esc_html__( 'None', 'wpfnl' ),
                'user_state'       => esc_html__( 'User State', 'wpfnl' ),
                'user_role'        => esc_html__( 'User Role', 'wpfnl' ),
                'browser'          => esc_html__( 'Browser', 'wpfnl' ),
                'operating_system' => esc_html__( 'Operating System', 'wpfnl' ),
                'day'              => esc_html__( 'Day', 'wpfnl' ),
            ],
            'inline'      => false,
            'default'     => 'none',
        ];

        // User State
        $this->controls['hide_from_logged_in'] = [
            'tab'      => 'content',
            'group'    => 'wpfunnels',
            'label'    => esc_html__( 'Hide From Logged In User', 'wpfnl' ),
            'type'     => 'checkbox',
            'default'  => false,
            'required' => [ 'display_condition_type', '=', 'user_state' ],
        ];

        $this->controls['hide_from_logged_out'] = [
            'tab'      => 'content',
            'group'    => 'wpfunnels',
            'label'    => esc_html__( 'Hide From Logged Out User', 'wpfnl' ),
            'type'     => 'checkbox',
            'default'  => false,
            'required' => [ 'display_condition_type', '=', 'user_state' ],
        ];

        // User Role
        $this->controls['hide_for_user_role'] = [
            'tab'      => 'content',
            'group'    => 'wpfunnels',
            'label'    => esc_html__( 'Hide For User Role', 'wpfnl' ),
            'type'     => 'select',
            'options'  => $this->get_wp_user_roles(),
            'default'  => 'none',
            'required' => [ 'display_condition_type', '=', 'user_role' ],
        ];

        // Browser
        $this->controls['hide_on_browser'] = [
            'tab'      => 'content',
            'group'    => 'wpfunnels',
            'label'    => esc_html__( 'Hide On Browser', 'wpfnl' ),
            'type'     => 'select',
            'options'  => [
                'none'       => esc_html__( 'None', 'wpfnl' ),
                'mozilla'    => esc_html__( 'Mozilla Firefox', 'wpfnl' ),
                'chrome'     => esc_html__( 'Google Chrome', 'wpfnl' ),
                'opera_mini' => esc_html__( 'Opera Mini', 'wpfnl' ),
                'safari'     => esc_html__( 'Safari', 'wpfnl' ),
                'edge'       => esc_html__( 'Microsoft Edge', 'wpfnl' ),
            ],
            'default'  => 'none',
            'required' => [ 'display_condition_type', '=', 'browser' ],
        ];

        // Operating System
        $this->controls['hide_on_os'] = [
            'tab'      => 'content',
            'group'    => 'wpfunnels',
            'label'    => esc_html__( 'Hide On Operating System', 'wpfnl' ),
            'type'     => 'select',
            'options'  => [
                'none'    => esc_html__( 'None', 'wpfnl' ),
                'ios'     => esc_html__( 'iOS', 'wpfnl' ),
                'android' => esc_html__( 'Android', 'wpfnl' ),
                'windows' => esc_html__( 'Windows', 'wpfnl' ),
                'macos'   => esc_html__( 'MacOS', 'wpfnl' ),
                'linux'   => esc_html__( 'Linux', 'wpfnl' ),
                'sunos'   => esc_html__( 'SunOS', 'wpfnl' ),
                'openbsd' => esc_html__( 'OpenBSD', 'wpfnl' ),
            ],
            'default'  => 'none',
            'required' => [ 'display_condition_type', '=', 'operating_system' ],
        ];

        // Day
        $this->controls['disable_on_days'] = [
            'tab'         => 'content',
            'group'       => 'wpfunnels',
            'label'       => esc_html__( 'Select Days You Want To Disable', 'wpfnl' ),
            'type'        => 'select',
            'multiple'    => true,
            'options'     => [
                'monday'    => esc_html__( 'Monday', 'wpfnl' ),
                'tuesday'   => esc_html__( 'Tuesday', 'wpfnl' ),
                'wednesday' => esc_html__( 'Wednesday', 'wpfnl' ),
                'thursday'  => esc_html__( 'Thursday', 'wpfnl' ),
                'friday'    => esc_html__( 'Friday', 'wpfnl' ),
                'saturday'  => esc_html__( 'Saturday', 'wpfnl' ),
                'sunday'    => esc_html__( 'Sunday', 'wpfnl' ),
            ],
            'default'     => [],
            'required'    => [ 'display_condition_type', '=', 'day' ],
        ];

        // Animation
        $this->controls['animationSeparator'] = [
            'tab'         => 'content',
            'group'       => 'wpfunnels',
            'label' => esc_html__( 'Animation', 'wpfnl' ),
            'type'  => 'separator',
        ];

        $this->controls['entrance_animation'] = [
            'tab'         => 'content',
            'group'       => 'wpfunnels',
            'label'       => esc_html__( 'Entrance Animation', 'wpfnl' ),
            'type'        => 'select',
            'options'     => [
                ''           => esc_html__( 'None', 'wpfnl' ),
                'fadeIn'     => esc_html__( 'Fade In', 'wpfnl' ),
                'fadeInUp'   => esc_html__( 'Fade In Up', 'wpfnl' ),
                'fadeInDown' => esc_html__( 'Fade In Down', 'wpfnl' ),
                'fadeInLeft' => esc_html__( 'Fade In Left', 'wpfnl' ),
                'fadeInRight'=> esc_html__( 'Fade In Right', 'wpfnl' ),
                'zoomIn'     => esc_html__( 'Zoom In', 'wpfnl' ),
                'zoomInUp'   => esc_html__( 'Zoom In Up', 'wpfnl' ),
                'bounceIn'   => esc_html__( 'Bounce In', 'wpfnl' ),
                'bounceInUp' => esc_html__( 'Bounce In Up', 'wpfnl' ),
                'slideInUp'  => esc_html__( 'Slide In Up', 'wpfnl' ),
                'slideInLeft'=> esc_html__( 'Slide In Left', 'wpfnl' ),
                'slideInRight'=> esc_html__( 'Slide In Right', 'wpfnl' ),
                'flipInX'    => esc_html__( 'Flip In X', 'wpfnl' ),
                'flipInY'    => esc_html__( 'Flip In Y', 'wpfnl' ),
                'pulse'      => esc_html__( 'Pulse', 'wpfnl' ),
                'tada'       => esc_html__( 'Tada', 'wpfnl' ),
                'wobble'     => esc_html__( 'Wobble', 'wpfnl' ),
            ],
            'inline'      => false,
            'placeholder' => esc_html__( 'None', 'wpfnl' ),
            'default'     => '',
            'description' => esc_html__( 'Animation plays when the order details enters the viewport on the live page.', 'wpfnl' ),
        ];
    }


    /**
     * Render the widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 3.1.0
     *
     * @access public
     */
    public function render()
    {
        $settings = $this->settings;

        // ---- Display Conditions ----------------------------------------
        $display_condition = ! empty( $settings['display_condition_type'] ) ? $settings['display_condition_type'] : 'none';

        if ( $display_condition === 'user_state' ) {
            $hide_logged_in  = ! empty( $settings['hide_from_logged_in'] );
            $hide_logged_out = ! empty( $settings['hide_from_logged_out'] );

            if ( $hide_logged_in && is_user_logged_in() ) {
                return;
            }
            if ( $hide_logged_out && ! is_user_logged_in() ) {
                return;
            }

        } elseif ( $display_condition === 'user_role' ) {
            $hide_for_user_role = ! empty( $settings['hide_for_user_role'] ) ? $settings['hide_for_user_role'] : 'none';

            if ( $hide_for_user_role !== 'none' && is_user_logged_in() ) {
                $user = wp_get_current_user();
                if ( in_array( $hide_for_user_role, $user->roles ) ) {
                    return;
                }
            }

        } elseif ( $display_condition === 'day' ) {
            $disable_on_days = ! empty( $settings['disable_on_days'] ) ? $settings['disable_on_days'] : [];

            if ( ! empty( $disable_on_days ) && is_array( $disable_on_days ) ) {
                $current_day = strtolower( date( 'l' ) ); // e.g., 'monday'
                if ( in_array( $current_day, $disable_on_days ) ) {
                    return;
                }
            }

        } elseif ( $display_condition === 'browser' ) {
            $hide_on_browser = ! empty( $settings['hide_on_browser'] ) ? $settings['hide_on_browser'] : 'none';

            if ( $hide_on_browser !== 'none' ) {
                $user_agent      = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
                $current_browser = '';

                if ( strpos( $user_agent, 'edg' ) !== false ) {
                    $current_browser = 'edge';
                } elseif ( strpos( $user_agent, 'opr' ) !== false || strpos( $user_agent, 'opera' ) !== false ) {
                    $current_browser = 'opera_mini';
                } elseif ( strpos( $user_agent, 'chrome' ) !== false ) {
                    $current_browser = 'chrome';
                } elseif ( strpos( $user_agent, 'safari' ) !== false ) {
                    $current_browser = 'safari';
                } elseif ( strpos( $user_agent, 'firefox' ) !== false ) {
                    $current_browser = 'mozilla';
                }

                if ( $current_browser === $hide_on_browser ) {
                    return;
                }
            }

        } elseif ( $display_condition === 'operating_system' ) {
            $hide_on_os = ! empty( $settings['hide_on_os'] ) ? $settings['hide_on_os'] : 'none';

            if ( $hide_on_os !== 'none' ) {
                $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? strtolower( $_SERVER['HTTP_USER_AGENT'] ) : '';
                $current_os = '';

                if ( strpos( $user_agent, 'windows' ) !== false || strpos( $user_agent, 'win32' ) !== false || strpos( $user_agent, 'win64' ) !== false ) {
                    $current_os = 'windows';
                } elseif ( strpos( $user_agent, 'macintosh' ) !== false || strpos( $user_agent, 'mac os x' ) !== false ) {
                    $current_os = 'macos';
                } elseif ( strpos( $user_agent, 'linux' ) !== false && strpos( $user_agent, 'android' ) === false ) {
                    $current_os = 'linux';
                } elseif ( strpos( $user_agent, 'android' ) !== false ) {
                    $current_os = 'android';
                } elseif ( strpos( $user_agent, 'iphone' ) !== false || strpos( $user_agent, 'ipad' ) !== false || strpos( $user_agent, 'ipod' ) !== false ) {
                    $current_os = 'ios';
                } elseif ( strpos( $user_agent, 'sunos' ) !== false ) {
                    $current_os = 'sunos';
                } elseif ( strpos( $user_agent, 'openbsd' ) !== false ) {
                    $current_os = 'openbsd';
                }

                if ( $current_os === $hide_on_os ) {
                    return;
                }
            }
        }
        // ---- End Display Conditions ------------------------------------

        $output 			= '';
		$order_overview 	= 'on';
		$order_details 		= 'on';
		$billing_details 	= 'on';
		$shipping_details 	= 'on';
        
        if (isset($settings['enable_order_review']) && !empty($settings['enable_order_review']) ) {
            $order_overview = $settings['enable_order_review'];
            update_post_meta($this->post_id, '_wpfnl_thankyou_order_overview', $settings['enable_order_review']);
        }else{
            $order_overview = 'off';
            update_post_meta( $this->post_id, '_wpfnl_thankyou_order_overview', 'off' );
        }

        if (isset($settings['enable_order_details']) && !empty($settings['enable_order_details']) ) {
			$order_details = $settings['enable_order_details'];
			update_post_meta($this->post_id, '_wpfnl_thankyou_order_details', $settings['enable_order_details']);
        }else{
            $order_details = 'off';
            update_post_meta( $this->post_id, '_wpfnl_thankyou_order_details', 'off' );
        }

        if (isset($settings['enable_billing_details']) && !empty($settings['enable_billing_details']) ) {
			$billing_details = $settings['enable_billing_details'];
			update_post_meta($this->post_id, '_wpfnl_thankyou_billing_details', $settings['enable_billing_details']);
        }else{
            $billing_details = 'off';
            update_post_meta( $this->post_id, '_wpfnl_thankyou_billing_details', 'off' );
        }

        if (isset($settings['enable_shipping_details']) && !empty($settings['enable_shipping_details']) ) {
			$shipping_details = $settings['enable_shipping_details'];
			update_post_meta($this->post_id, '_wpfnl_thankyou_shipping_details', $settings['enable_shipping_details']);
        }else{
            $shipping_details = 'off';
            update_post_meta( $this->post_id, '_wpfnl_thankyou_shipping_details', 'off' );
        }

        // Build wrapper classes
        $wrapper_classes = 'wpfnl-bricks-order-details-form';
        $wrapper_classes .= ' wpfnl-bricks-display-order-overview-' . esc_attr( $order_overview );
        $wrapper_classes .= ' wpfnl-bricks-display-order-details-' . esc_attr( $order_details );
        $wrapper_classes .= ' wpfnl-bricks-display-billing-address-' . esc_attr( $billing_details );
        $wrapper_classes .= ' wpfnl-bricks-display-shipping-address-' . esc_attr( $shipping_details );

        // Add responsive classes
        if ( ! empty( $settings['hide_on_desktop'] ) ) {
            $wrapper_classes .= ' wpfnl-hide-desktop';
        }
        if ( ! empty( $settings['hide_on_tablet'] ) ) {
            $wrapper_classes .= ' wpfnl-hide-tablet';
        }
        if ( ! empty( $settings['hide_on_mobile'] ) ) {
            $wrapper_classes .= ' wpfnl-hide-mobile';
        }

        // Add animation class
        $animation = ! empty( $settings['entrance_animation'] ) ? $settings['entrance_animation'] : '';
        if ( $animation ) {
            $wrapper_classes .= ' wpfnl-animation ' . esc_attr( $animation );
        }
       
       	?>
		<?php if( !isset($_GET['optin']) ) { 
            ?>
			<div class="<?php echo esc_attr( $wrapper_classes ); ?>">
				<?php 
                    echo do_shortcode( '[wpfunnels_order_details step_id='.$this->post_id.']' ); 
                ?>
			</div>
		<?php }

    }

}
