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
     * Set builder control groups
     * 
     * @since 3.1.0
     */
    public function set_control_groups()
    {
        $this->control_groups['wpfunnels'] = [
            'title' => esc_html__('wpfunnels', 'wpfnl'), // Localized control group title
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

        $output 			= '';
		$order_overview 	= 'on';
		$order_details 		= 'on';
		$billing_details 	= 'on';
		$shipping_details 	= 'on';
        
        if (isset($this->settings['enable_order_review']) && !empty($this->settings['enable_order_review']) ) {
            $order_overview = $this->settings['enable_order_review'];
            update_post_meta($this->post_id, '_wpfnl_thankyou_order_overview', $this->settings['enable_order_review']);
        }else{
            $order_overview = 'off';
            update_post_meta( $this->post_id, '_wpfnl_thankyou_order_overview', 'off' );
        }

        if (isset($this->settings['enable_order_details']) && !empty($this->settings['enable_order_details']) ) {
			$order_details = $this->settings['enable_order_details'];
			update_post_meta($this->post_id, '_wpfnl_thankyou_order_details', $this->settings['enable_order_details']);
        }else{
            $order_details = 'off';
            update_post_meta( $this->post_id, '_wpfnl_thankyou_order_details', 'off' );
        }

        if (isset($this->settings['enable_billing_details']) && !empty($this->settings['enable_billing_details']) ) {
			$billing_details = $this->settings['enable_billing_details'];
			update_post_meta($this->post_id, '_wpfnl_thankyou_billing_details', $this->settings['enable_billing_details']);
        }else{
            $billing_details = 'off';
            update_post_meta( $this->post_id, '_wpfnl_thankyou_billing_details', 'off' );
        }

        if (isset($this->settings['enable_shipping_details']) && !empty($this->settings['enable_shipping_details']) ) {
			$shipping_details = $this->settings['enable_shipping_details'];
			update_post_meta($this->post_id, '_wpfnl_thankyou_shipping_details', $this->settings['enable_shipping_details']);
        }else{
            $shipping_details = 'off';
            update_post_meta( $this->post_id, '_wpfnl_thankyou_shipping_details', 'off' );
        }
       
       	?>
		<?php if( !isset($_GET['optin']) ) { 
            ?>
			<div class = "wpfnl-bricks-order-details-form wpfnl-bricks-display-order-overview-<?php echo esc_attr( $order_overview ); ?> wpfnl-bricks-display-order-details-<?php echo esc_attr( $order_details ); ?> wpfnl-bricks-display-billing-address-<?php echo esc_attr( $billing_details ); ?> wpfnl-bricks-display-shipping-address-<?php echo esc_attr( $shipping_details ); ?>">
				<?php 
                    echo do_shortcode( '[wpfunnels_order_details step_id='.$this->post_id.']' ); 
                ?>
			</div>
		<?php }

    }

}
