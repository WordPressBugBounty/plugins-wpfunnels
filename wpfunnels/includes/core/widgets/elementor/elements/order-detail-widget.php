<?php
/**
 * Order details
 * 
 * @package
 */
namespace WPFunnels\Widgets\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Stack;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

/**
 * Funnel sell Reject button
 *
 * @since 1.0.0
 */
class Order_Details extends Widget_Base
{

    /**
     * Register the widget controls.
     *
     * Adds different input fields to allow the user to change and Wpvrize the widget settings.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    protected function init_controls() {
        if ( version_compare(ELEMENTOR_VERSION, '3.1.0', '>=') ) {
            $this->register_controls();
        } else {
            $this->_register_controls();
        }
    }


    
    /**
     * Retrieve the widget name.
     *
     * @return string Widget name.
     * @since  1.0.0
     *
     * @access public
     */
    public function get_name()
    {
        return 'wpfnl-order-detail';
    }

    /**
     * Retrieve the widget title.
     *
     * @return string Widget title.
     * @since  1.0.0
     *
     * @access public
     */
    public function get_title()
    {
        return __('Order Detail', 'wpfnl');
    }

    /**
     * Retrieve the widget icon.
     *
     * @return string Widget icon.
     * @since  1.0.0
     *
     * @access public
     */
    public function get_icon()
    {
        return 'icon-wpfnl order-details';
    }

    /**
     * Retrieve the list of categories the widget belongs to.
     *
     * Used to determine where to display the widget in the editor.
     *
     * Note that currently Elementor supports only one category.
     * When multiple categories passed, Elementor uses the first one.
     *
     * @return array Widget categories.
     * @since  1.0.0
     *
     * @access public
     */
    public function get_categories()
    {
        return ['wp-funnel'];
    }

    /**
     * Retrieve the list of scripts the widget depended on.
     *
     * Used to set scripts dependencies required to run the widget.
     *
     * @return array Widget scripts dependencies.
     * @since  1.0.0
     *
     * @access public
     */
    public function get_script_depends()
    {
        return ['funnel-order-detail-widget'];
    }


    /**
     * Register the widget controls.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    protected function register_controls()
    {
        $this->wpfnl_order_details_controls();

    }

    /**
     * Register the widget controls.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    protected function _register_controls()
    {
        $this->wpfnl_order_details_controls();

    }

    /**
     * Get user roles.
     *
     * Retrieve an array of WordPress user roles.
     *
     * @return array An array containing user roles.
     * @since  1.0.0
     * @access public
     */
    public function get_user_roles()
    {
        $roles = array(
            'none' => __('None', 'wpfnl'),
        );
        
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
        }
        
        $wp_roles = get_editable_roles();
        
        foreach ($wp_roles as $role_key => $role_info) {
            $roles[$role_key] = $role_info['name'];
        }
        
        return $roles;
    }

    /**
     * Order details controls.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    protected function wpfnl_order_details_controls(){
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Funnel Order Details', 'wpfnl'),
            ]
        );

        $wpfnl_thankyou_order_overview = get_post_meta(get_the_id(), '_wpfnl_thankyou_order_overview', true);
        $wpfnl_thankyou_order_details = get_post_meta(get_the_id(), '_wpfnl_thankyou_order_details', true);
        $wpfnl_thankyou_billing_details = get_post_meta(get_the_id(), '_wpfnl_thankyou_billing_details', true);
        $wpfnl_thankyou_shipping_details = get_post_meta(get_the_id(), '_wpfnl_thankyou_shipping_details', true);

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

        $this->add_control(
            'enable_order_review',
            [
                'label' => __('Show Order Overview', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => $wpfnl_thankyou_order_overview,
                'label_on' => __( 'On', 'wpfnl' ),
				'label_off' => __( 'Off', 'wpfnl' ),
                'return_value' => 'on',
            ]
        );

        $this->add_control(
            'enable_order_details',
            [
                'label' => __('Show Order Details', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => $wpfnl_thankyou_order_details,
                'label_on' => __( 'On', 'wpfnl' ),
				'label_off' => __( 'Off', 'wpfnl' ),
                'return_value' => 'on',
            ]
        );

        $this->add_control(
            'enable_billing_details',
            [
                'label' => __('Show Billing Details', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => $wpfnl_thankyou_billing_details,
                'label_on' => __( 'On', 'wpfnl' ),
				'label_off' => __( 'Off', 'wpfnl' ),
                'return_value' => 'on',
            ]
        );

        $this->add_control(
            'enable_shipping_details',
            [
                'label' => __('Show Shipping Details', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'default' => $wpfnl_thankyou_shipping_details,
                'label_on' => __( 'On', 'wpfnl' ),
				'label_off' => __( 'Off', 'wpfnl' ),
                'return_value' => 'on',
            ]
        );

        $this->end_controls_section();

        // Display Conditions Section
        $this->start_controls_section(
            'section_display_conditions',
            [
                'label' => __('Display Conditions', 'wpfnl'),
            ]
        );

        $this->add_control(
            'display_condition_type',
            [
                'label' => __('Display Condition', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => __('None', 'wpfnl'),
                    'user_state' => __('User State', 'wpfnl'),
                    'user_role' => __('User Role', 'wpfnl'),
                    'browser' => __('Browser', 'wpfnl'),
                    'operating_system' => __('Operating System', 'wpfnl'),
                    'day' => __('Day', 'wpfnl'),
                ],
            ]
        );

        // User State Conditions
        $this->add_control(
            'hide_from_logged_in',
            [
                'label' => __('Hide From Logged In User', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wpfnl'),
                'label_off' => __('No', 'wpfnl'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'display_condition_type' => 'user_state',
                ],
            ]
        );

        $this->add_control(
            'hide_from_logged_out',
            [
                'label' => __('Hide From Logged Out User', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'wpfnl'),
                'label_off' => __('No', 'wpfnl'),
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => [
                    'display_condition_type' => 'user_state',
                ],
            ]
        );

        // User Role Condition
        $this->add_control(
            'hide_for_user_role',
            [
                'label' => __('Hide For User Role', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'none',
                'options' => $this->get_user_roles(),
                'condition' => [
                    'display_condition_type' => 'user_role',
                ],
            ]
        );

        // Browser Condition
        $this->add_control(
            'hide_on_browser',
            [
                'label' => __('Hide On Browser', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => __('None', 'wpfnl'),
                    'mozilla' => __('Mozilla Firefox', 'wpfnl'),
                    'chrome' => __('Google Chrome', 'wpfnl'),
                    'opera_mini' => __('Opera Mini', 'wpfnl'),
                    'safari' => __('Safari', 'wpfnl'),
                    'edge' => __('Microsoft Edge', 'wpfnl'),
                ],
                'condition' => [
                    'display_condition_type' => 'browser',
                ],
            ]
        );

        // Operating System Condition
        $this->add_control(
            'hide_on_os',
            [
                'label' => __('Hide On Operating System', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'default' => 'none',
                'options' => [
                    'none' => __('None', 'wpfnl'),
                    'ios' => __('iOS', 'wpfnl'),
                    'android' => __('Android', 'wpfnl'),
                    'windows' => __('Windows', 'wpfnl'),
                    'macos' => __('MacOS', 'wpfnl'),
                    'linux' => __('Linux', 'wpfnl'),
                    'sunos' => __('SunOS', 'wpfnl'),
                    'openbsd' => __('OpenBSD', 'wpfnl'),
                ],
                'condition' => [
                    'display_condition_type' => 'operating_system',
                ],
            ]
        );

        // Day Condition
        $this->add_control(
            'disable_on_days',
            [
                'label' => __('Select Days You Want To Disable', 'wpfnl'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'multiple' => true,
                'options' => [
                    'monday' => __('Monday', 'wpfnl'),
                    'tuesday' => __('Tuesday', 'wpfnl'),
                    'wednesday' => __('Wednesday', 'wpfnl'),
                    'thursday' => __('Thursday', 'wpfnl'),
                    'friday' => __('Friday', 'wpfnl'),
                    'saturday' => __('Saturday', 'wpfnl'),
                    'sunday' => __('Sunday', 'wpfnl'),
                ],
                'condition' => [
                    'display_condition_type' => 'day',
                ],
            ]
        );

        $this->end_controls_section();

    }


    /**
     * Render the widget output on the frontend.
     *
     * Written in PHP and used to generate the final HTML.
     *
     * @since 1.0.0
     *
     * @access protected
     */
    protected function render()
    {
		$output 			= '';
		$order_overview 	= 'on';
		$order_details 		= 'on';
		$billing_details 	= 'on';
		$shipping_details 	= 'on';
        $settings = $this->get_settings();

        // Get display condition settings
        $display_condition = isset($settings['display_condition_type']) ? $settings['display_condition_type'] : 'none';
        
        // Check display conditions and return early if widget should be hidden
        if ($display_condition === 'user_state') {
            $hide_logged_in = isset($settings['hide_from_logged_in']) ? $settings['hide_from_logged_in'] : 'no';
            $hide_logged_out = isset($settings['hide_from_logged_out']) ? $settings['hide_from_logged_out'] : 'no';
            
            // Hide from logged in users
            if ($hide_logged_in === 'yes' && is_user_logged_in()) {
                return;
            }
            
            // Hide from logged out users
            if ($hide_logged_out === 'yes' && !is_user_logged_in()) {
                return;
            }

        } elseif ($display_condition === 'user_role') {
            $hide_for_user_role = isset($settings['hide_for_user_role']) ? $settings['hide_for_user_role'] : 'none';
            
            if ($hide_for_user_role !== 'none' && is_user_logged_in()) {
                $user = wp_get_current_user();
                if (in_array($hide_for_user_role, $user->roles)) {
                    return;
                }
            }

        } elseif ($display_condition === 'day') {
            $disable_on_days = isset($settings['disable_on_days']) ? $settings['disable_on_days'] : array();
            
            if (!empty($disable_on_days) && is_array($disable_on_days)) {
                $current_day = strtolower(date('l')); // e.g., 'monday'
                if (in_array($current_day, $disable_on_days)) {
                    return;
                }
            }

        } elseif ($display_condition === 'browser') {
            $hide_on_browser = isset($settings['hide_on_browser']) ? $settings['hide_on_browser'] : 'none';
            
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
                
                // Hide widget if current browser matches
                if ($current_browser === $hide_on_browser) {
                    return;
                }
            }
        } elseif ($display_condition === 'operating_system') {
            $hide_on_os = isset($settings['hide_on_os']) ? $settings['hide_on_os'] : 'none';
            
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
                
                // Hide widget if current OS matches
                if ($current_os === $hide_on_os) {
                    return;
                }
            }
        }
        // -----end display conditioning----

        if (isset($settings['enable_order_review']) && !empty($settings['enable_order_review']) ) {
            $order_overview = $settings['enable_order_review'];
            update_post_meta(get_the_ID(), '_wpfnl_thankyou_order_overview', $settings['enable_order_review']);
        }else{
            $order_overview = 'off';
            update_post_meta( get_the_ID(), '_wpfnl_thankyou_order_overview', 'off' );
        }

        if (isset($settings['enable_order_details']) && !empty($settings['enable_order_details']) ) {
			$order_details = $settings['enable_order_details'];
			update_post_meta(get_the_ID(), '_wpfnl_thankyou_order_details', $settings['enable_order_details']);
        }else{
            $order_details = 'off';
            update_post_meta( get_the_ID(), '_wpfnl_thankyou_order_details', 'off' );
        }

        if (isset($settings['enable_billing_details']) && !empty($settings['enable_billing_details']) ) {
			$billing_details = $settings['enable_billing_details'];
			update_post_meta(get_the_ID(), '_wpfnl_thankyou_billing_details', $settings['enable_billing_details']);
        }else{
            $billing_details = 'off';
            update_post_meta( get_the_ID(), '_wpfnl_thankyou_billing_details', 'off' );
        }

        if (isset($settings['enable_shipping_details']) && !empty($settings['enable_shipping_details']) ) {
			$shipping_details = $settings['enable_shipping_details'];
			update_post_meta(get_the_ID(), '_wpfnl_thankyou_shipping_details', $settings['enable_shipping_details']);
        }else{
            $shipping_details = 'off';
            update_post_meta( get_the_ID(), '_wpfnl_thankyou_shipping_details', 'off' );
        }

       	?>
		<?php if( !isset($_GET['optin']) ) { ?>
			<div class = "wpfnl-elementor-order-details-form wpfnl-elementor-display-order-overview-<?php echo esc_attr( $order_overview ); ?> wpfnl-elementor-display-order-details-<?php echo esc_attr( $order_details ); ?> wpfnl-elementor-display-billing-address-<?php echo esc_attr( $billing_details ); ?> wpfnl-elementor-display-shipping-address-<?php echo esc_attr( $shipping_details ); ?>">
				<?php echo do_shortcode( '[wpfunnels_order_details]' ); ?>
			</div>
		<?php }

		echo $output;
    }

}
