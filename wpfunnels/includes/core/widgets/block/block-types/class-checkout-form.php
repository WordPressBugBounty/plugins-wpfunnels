<?php
/**
 * Checkout form
 * 
 * @package
 */
namespace WPFunnels\Widgets\Gutenberg\BlockTypes;


use WPFunnels\Meta\Wpfnl_Default_Meta;
use WPFunnels\Wpfnl;
use WPFunnels\Wpfnl_functions;

/**
 * Featured Product class.
 */
class CheckoutForm extends AbstractDynamicBlock {

    protected $defaults = array(
        'layout'   => 'wpfnl-col-2',
        'floating_label'   => '',
        'billingHeaderColor'   => '#363B4E',
        'billingHeaderMarginTop'   => '',
        'billingHeaderMarginRight'   => '',
        'billingHeaderMarginBottom'   => '',
        'billingHeaderMarginLeft'   => '',
        'billingHeaderPaddingTop'   => '15',
        'billingHeaderPaddingRight'   => '20',
        'billingHeaderPaddingBottom'   => '15',
        'billingHeaderPaddingLeft'   => '20',
        'billingLabelColor'   => '#363B4E',
        'billingLabelMarginTop'   => '',
        'billingLabelMarginRight'   => '',
        'billingLabelMarginBottom'   => '',
        'billingLabelMarginLeft'   => '',
        'billingFieldBackgroundColor'   => 'transparent',
        'billingFieldTextColor'   => '#363B4E',
        'billingInputsBorderRadius'   => '4',
        'billingInputMarginTop'   => '0',
        'billingInputMarginRight'   => '0',
        'billingInputMarginBottom'   => '0',
        'billingInputMarginLeft'   => '0',
        'billingInputPaddingTop'   => '10',
        'billingInputPaddingRight'   => '20',
        'billingInputPaddingBottom'   => '10',
        'billingInputPaddingLeft'   => '20',
        'billingInputBorderStyle'   => 'solid',
        'billingInputBorderWidth'   => '1',
        'billingInputBorderColor'   => '#E5E8F3',

        'shippingHeaderColor'   => '#363B4E',
        'shippingHeaderMarginTop'   => '',
        'shippingHeaderMarginRight'   => '',
        'shippingHeaderMarginBottom'   => '',
        'shippingHeaderMarginLeft'   => '',
        'shippingHeaderPaddingTop'   => '15',
        'shippingHeaderPaddingRight'   => '20',
        'shippingHeaderPaddingBottom'   => '15',
        'shippingHeaderPaddingLeft'   => '20',
        'shippingLabelColor'   => '#363B4E',
        'shippingLabelMarginTop'   => '',
        'shippingLabelMarginRight'   => '',
        'shippingLabelMarginBottom'   => '',
        'shippingLabelMarginLeft'   => '',
        'shippingFieldTextColor'   => '#363B4E',
        'shippingFieldBackgroundColor'   => 'transparent',
        'shippingInputsBorderRadius'   => '4',
        'shippingInputMarginTop' => '0',
        'shippingInputMarginRight' => '0',
        'shippingInputMarginBottom' => '0',
        'shippingInputMarginLeft' => '0',
        'shippingInputPaddingTop' => '10',
        'shippingInputPaddingRight' => '20',
        'shippingInputPaddingBottom' => '10',
        'shippingInputPaddingLeft' => '20',
        'shippingInputBorderStyle' => 'solid',
        'shippingInputBorderWidth' => '1',
        'shippingInputBorderColor' => '#E5E8F3',

        'orderHeaderColor'   => '#363B4E',
        'orderHeaderMarginTop'   => '',
        'orderHeaderMarginRight'   => '',
        'orderHeaderMarginBottom'   => '',
        'orderHeaderMarginLeft'   => '',
        'orderHeaderPaddingTop'   => '15',
        'orderHeaderPaddingRight'   => '20',
        'orderHeaderPaddingBottom'   => '15',
        'orderHeaderPaddingLeft'   => '20',
        'orderTableBorderColor'   => '#E5E8F3',
        'orderTableTextColor'   => '#363B4E',

        'paymentRadioBtnLabelColor'   => '#363B4E',
        'paymentSectionTextColor'   => '#363B4E',
        'paymentSectionBgColor'   => '#ffffff',
        'paymentSectionLinkColor'   => '#4C25A5',
        'paymentBoxTextColor'   => '#515151',
        'paymentBoxBgColor'   => '#F5F5FF',
        'orderButtonBgColor'   => '#6E42D3',
        'orderButtonTextColor'   => '#ffffff',

        'stepTitleColor'   => '#363B4E',
        'stepLineColor'   => '#eee',
        'boxBgColor'   => '#e8e8ed',
        'stepIconColor'   => '#6E42D3',
        'boxBorderColor'   => '#ffffff',
        'stepLineActiveColor'   => '#6E42D3',
        'boxBgActiveColor'   => '#6E42D3',
        'stepIconActiveColor'   => '#ffffff',
        'boxBorderActiveColor'   => '#6E42D3',
        'stepNavigationBtnColor'   => '#ffffff',
        'stepNavigationBtnBgColor'   => '#6E42D3',
        'stepNavigationBtnPaddingTop'   => '14',
        'stepNavigationBtnPaddingRight'   => '25',
        'stepNavigationBtnPaddingBottom'   => '14',
        'stepNavigationBtnPaddingLeft'   => '25',
        'stepNavigationBtnHvrColor'   => '',
        'stepNavigationBtnHvrBgColor'   => '',

        'placeOrderBtnText'    => 'Place Order',
        'placeOrderSubText'    => '',
        'placeOrderEnableIcon' => false,
        'placeOrderIconStyle'  => 'lock1',
        'placeOrderEnablePrice'=> false,
        'placeOrderBelowText'  => '',
    );


    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'checkout-form';


	public function __construct( $block_name = '' )
	{
		parent::__construct($block_name);
		add_action('wp_ajax_show_checkout_markup', [$this, 'show_checkout_markup']);
		add_action( 'wpfunnels/gutenberg_checkout_dynamic_filters', array($this, 'dynamic_filters') );
		add_action( 'woocommerce_checkout_update_order_review', array( $this, 'apply_place_order_filter_on_ajax_update' ) );
	}



	/**
     * Fired during WooCommerce AJAX checkout update (update_order_review).
     * Reads Place Order settings from post_meta and applies the button filter
     * so the button is customized even during payment section re-renders.
     *
     * @param string $post_data URL-encoded post data string from the AJAX request.
     */
    public function apply_place_order_filter_on_ajax_update( $post_data ) {
        $parsed = array();
        wp_parse_str( $post_data, $parsed );
        $checkout_id = isset( $parsed['_wpfunnels_checkout_id'] ) ? intval( $parsed['_wpfunnels_checkout_id'] ) : 0;
        if ( ! $checkout_id ) {
            $checkout_id = intval( \WPFunnels\Wpfnl_functions::get_checkout_id_from_post_data() );
        }
        if ( ! $checkout_id ) {
            $checkout_id = intval( \WPFunnels\Wpfnl_functions::get_checkout_id_from_post( $_POST ) );
        }
        if ( ! $checkout_id ) {
            return;
        }
        $settings = get_post_meta( $checkout_id, '_wpfnl_place_order_settings', true );
        if ( empty( $settings ) || ! is_array( $settings ) ) {
            return;
        }
        $place_order_filter = $this->get_place_order_button_filter( $settings );
        add_filter( 'woocommerce_order_button_html', $place_order_filter );

        $below_text = isset( $settings['placeOrderBelowText'] ) ? $settings['placeOrderBelowText'] : '';
        if ( ! empty( $below_text ) ) {
            add_action( 'woocommerce_review_order_after_submit', function() use ( $below_text ) {
                echo '<div class="wpfnl-below-place-order-btn">' . wp_kses_post( $below_text ) . '</div>';
            } );
        }
    }


	/**
     * Build and return the woocommerce_order_button_html filter closure.
     *
     * @param array $attributes Block attributes.
     * @return \Closure
     */
    protected function get_place_order_button_filter( $attributes ) {
        $btn_text     = isset( $attributes['placeOrderBtnText'] ) && '' !== $attributes['placeOrderBtnText'] ? $attributes['placeOrderBtnText'] : 'Place Order';
        $sub_text     = isset( $attributes['placeOrderSubText'] ) ? $attributes['placeOrderSubText'] : '';
        $enable_icon  = isset( $attributes['placeOrderEnableIcon'] ) ? (bool) $attributes['placeOrderEnableIcon'] : false;
        $icon_style   = isset( $attributes['placeOrderIconStyle'] ) ? $attributes['placeOrderIconStyle'] : 'lock1';
        $enable_price = isset( $attributes['placeOrderEnablePrice'] ) ? (bool) $attributes['placeOrderEnablePrice'] : false;

        $icon_svgs = array(
            'lock1'    => '<svg width="24" height="24" viewBox="0 0 24 24"><rect x="5" y="11" width="14" height="10" rx="2" fill="currentColor"/><path d="M8 11V7a4 4 0 118 0v4" stroke="white" stroke-width="2"/></svg>',
            'lock2'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 11V7a4 4 0 118 0v4" stroke="currentColor" stroke-width="2"/></svg>',
            'cart1'    => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M6 6h15l-2 9H8L6 6z" stroke="currentColor" stroke-width="2"/><circle cx="9" cy="20" r="1.5" fill="currentColor"/><circle cx="18" cy="20" r="1.5" fill="currentColor"/></svg>',
            'checkout' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M5 13l4 4L19 7" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'arrow1'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M5 12h14M13 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'arrow2'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'arrow3'   => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M6 13l6 6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        );

        return function( $button_html ) use ( $btn_text, $sub_text, $enable_icon, $icon_style, $enable_price, $icon_svgs ) {
            $icon_html = '';
            if ( $enable_icon ) {
                $icon_svg  = isset( $icon_svgs[ $icon_style ] ) ? $icon_svgs[ $icon_style ] : $icon_svgs['lock1'];
                $icon_html = '<span class="wpfnl-place-order-icon">' . $icon_svg . '</span>';
            }

            $price_html = '';
            if ( $enable_price && function_exists( 'WC' ) && is_object( WC()->cart ) ) {
                $price_html = ' <span class="wpfnl-place-order-price">' . WC()->cart->get_total() . '</span>';
            }

            $sub_text_html = '';
            if ( ! empty( $sub_text ) ) {
                $sub_text_html = '<span class="wpfnl-place-order-sub-text">' . esc_html( $sub_text ) . '</span>';
            }

            $label = esc_html( $btn_text );
            return '<button type="submit" class="button alt" name="woocommerce_checkout_place_order" id="place_order" value="' . $label . '" data-value="' . $label . '">'
                . $icon_html
                . '<span class="wpfnl-place-order-text">' . $label . '</span>'
                . $price_html
                . $sub_text_html
                . '</button>';
        };
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
    	global $post;
    	$checkout_id = $post->ID;
		$checkout_layout = isset( $attributes[ 'layout' ] ) ? $attributes[ 'layout' ] : 'wpfnl-col-1';
        $floating_label = isset( $attributes[ 'floating_label' ] ) ? $attributes[ 'floating_label' ] : '';
		update_post_meta( $checkout_id, '_wpfnl_checkout_layout', $checkout_layout );
        update_post_meta( $checkout_id, '_wpfnl_floating_label', $floating_label );
        $attributes = wp_parse_args( $attributes, $this->defaults );

        $dynamic_css = $this->generate_assets( $attributes );
        do_action( 'wpfunnels/gutenberg_checkout_dynamic_filters', $attributes );
        do_action( 'wpfunnels/before_checkout_form', $checkout_id );

        // Persist Place Order settings so WooCommerce AJAX checkout updates can apply them.
        update_post_meta( $checkout_id, '_wpfnl_place_order_settings', array(
            'placeOrderBtnText'    => isset( $attributes['placeOrderBtnText'] ) ? $attributes['placeOrderBtnText'] : 'Place Order',
            'placeOrderSubText'    => isset( $attributes['placeOrderSubText'] ) ? $attributes['placeOrderSubText'] : '',
            'placeOrderEnableIcon' => isset( $attributes['placeOrderEnableIcon'] ) ? (bool) $attributes['placeOrderEnableIcon'] : false,
            'placeOrderIconStyle'  => isset( $attributes['placeOrderIconStyle'] ) ? $attributes['placeOrderIconStyle'] : 'lock1',
            'placeOrderEnablePrice'=> isset( $attributes['placeOrderEnablePrice'] ) ? (bool) $attributes['placeOrderEnablePrice'] : false,
            'placeOrderBelowText'  => isset( $attributes['placeOrderBelowText'] ) ? $attributes['placeOrderBelowText'] : '',
        ) );

        $place_order_filter = $this->get_place_order_button_filter( $attributes );
        add_filter( 'woocommerce_order_button_html', $place_order_filter );

        $below_text = isset( $attributes['placeOrderBelowText'] ) ? $attributes['placeOrderBelowText'] : '';
        $below_btn_filter = null;
        if ( ! empty( $below_text ) ) {
            $below_btn_filter = function() use ( $below_text ) {
                echo '<div class="wpfnl-below-place-order-btn">' . wp_kses_post( $below_text ) . '</div>';
            };
            add_action( 'woocommerce_review_order_after_submit', $below_btn_filter );
        }

        $output  = sprintf( '<div class="%1s" style="%2s">', esc_attr( $this->get_classes( $attributes ) ), esc_attr( $this->get_styles( $attributes ) ) );
        $output .= '<div class="wpfnl-block-checkout-form__wrapper wp-block-wpfunnels-checkout">';
        $output .= do_shortcode('[wpfunnels_checkout]');
        $output .= '</div>';
        $output .= '</div>';

        remove_filter( 'woocommerce_order_button_html', $place_order_filter );
        if ( null !== $below_btn_filter ) {
            remove_action( 'woocommerce_review_order_after_submit', $below_btn_filter );
        }

        return "<style>$dynamic_css</style>".$output;
    }


    /**
     * Dynamic filters for checkout form
     *
     * @param $attributes
     *
     * @since 2.0.3
     */
    public function dynamic_filters( $attributes ) {
        $checkout_meta = array(
            array(
                'name'      => 'layout',
                'meta_key'  => 'wpfnl_checkout_layout'
            ),
            array(
                'name'      => 'floating_label',
                'meta_key'  => 'wpfnl_floating_label'
            ),
        );
        foreach ( $checkout_meta as $key => $meta ) {
            $meta_key = $meta['meta_key'];
            $meta_name = $meta['name'];
            add_filter(
                "wpfunnels/checkout_meta_{$meta_key}",
                function ( $value ) use ( $attributes, $meta_name ) {
                    $value = sanitize_text_field( wp_unslash( $attributes[$meta_name] ) );
                    return $value;
                },
                10, 1
            );
        }
    }


    /**
     * Get generated dynamic styles from $attributes
     *
     * @param $attributes
     * @param $post
     * 
     * @return array|string
     */
    protected function get_generated_dynamic_styles( $attributes, $post ) {
        $selectors = array(

            /* ----billing section style----- */
            '.wpfnl-checkout .woocommerce-billing-fields > h3,
            .wpfnl-checkout .woocommerce-billing-fields h3 span' => array(
                'color' => $attributes['billingHeaderColor'],
                'margin-top' => $attributes['billingHeaderMarginTop'].'px',
                'margin-right' => $attributes['billingHeaderMarginRight'].'px',
                'margin-bottom' => $attributes['billingHeaderMarginBottom'].'px',
                'margin-left' => $attributes['billingHeaderMarginLeft'].'px',
                'padding-top' => $attributes['billingHeaderPaddingTop'].'px',
                'padding-right' => $attributes['billingHeaderPaddingRight'].'px',
                'padding-bottom' => $attributes['billingHeaderPaddingBottom'].'px',
                'padding-left' => $attributes['billingHeaderPaddingLeft'].'px',
            ),

            '.wpfnl-checkout .woocommerce-billing-fields > label,
            .wpfnl-checkout .woocommerce-billing-fields p.form-row > label' => array(
                'color' => $attributes['billingLabelColor'],
                'margin-top' => $attributes['billingLabelMarginTop'].'px',
                'margin-right' => $attributes['billingLabelMarginRight'].'px',
                'margin-bottom' => $attributes['billingLabelMarginBottom'].'px',
                'margin-left' => $attributes['billingLabelMarginLeft'].'px',
            ),

            //---billing input fields----
            '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_billing .form-row:not(.create-account) > label' => array(
                'background-color' => $attributes['billingFieldBackgroundColor'],
            ),

            '.wpfnl-checkout .woocommerce-billing-fields .form-row input.input-text,
            .wpfnl-checkout .woocommerce-account-fields .form-row input.input-text,
            .wpfnl-checkout .woocommerce-billing-fields .form-row textarea,
            .wpfnl-checkout .woocommerce-billing-fields .select2-container--default .select2-selection--single,
            .wpfnl-checkout .woocommerce-billing-fields .select2-container--default .select2-selection--single .select2-selection__rendered,
            .wpfnl-checkout .woocommerce-billing-fields .form-row select.select,
            .wpfnl-checkout .woocommerce-billing-fields .form-row select' => array(
                'background-color' => $attributes['billingFieldBackgroundColor'],
                'color' => $attributes['billingFieldTextColor'],
            ),
            '.wpfnl-checkout .woocommerce .woocommerce-billing-fields .form-row input.input-text,
            .wpfnl-checkout .woocommerce .woocommerce-billing-fields .form-row textarea,
            .wpfnl-checkout .woocommerce .woocommerce-billing-fields .form-row select.select,
            .wpfnl-checkout .woocommerce .woocommerce-billing-fields .form-row select' => array(
                'margin' => "".$attributes['billingInputMarginTop']."px ".$attributes['billingInputMarginRight']."px ".$attributes['billingInputMarginBottom']."px ".$attributes['billingInputMarginLeft']."px",
                'padding' => "".$attributes['billingInputPaddingTop']."px ".$attributes['billingInputPaddingRight']."px ".$attributes['billingInputPaddingBottom']."px ".$attributes['billingInputPaddingLeft']."px",
                'line-height' => '20px',
                'border-style' => $attributes['billingInputBorderStyle'],
                'border-width' => "".$attributes['billingInputBorderWidth']."px",
                'border-color' => $attributes['billingInputBorderColor'],
            ),
            '.wpfnl-checkout .woocommerce-billing-fields .select2-container--default .select2-selection--single' => array(
                'border-style' => $attributes['billingInputBorderStyle'],
                'border-width' => "".$attributes['billingInputBorderWidth']."px",
                'border-color' => $attributes['billingInputBorderColor'],
            ),
            '.wpfnl-checkout .woocommerce .woocommerce-billing-fields .form-row .select2-selection__rendered' => array(
                'padding' => "".$attributes['billingInputPaddingTop']."px ".$attributes['billingInputPaddingRight']."px ".$attributes['billingInputPaddingBottom']."px ".$attributes['billingInputPaddingLeft']."px",
                'line-height' => '20px',
            ),

            '.wpfnl-checkout .woocommerce-billing-fields ::placeholder,
            .wpfnl-checkout .woocommerce-billing-fields ::-webkit-input-placeholder' => array(
                'color' => $attributes['billingFieldTextColor'],
            ),

            '.wpfnl-checkout form .woocommerce-billing-fields .form-row input.input-text,
            .wpfnl-checkout form .woocommerce-billing-fields .form-row textarea,
            .wpfnl-checkout .select2-container--default .select2-selection--single,
            .wpfnl-checkout .woocommerce-billing-fields .select2-container--default .select2-selection--single .select2-selection__rendered,
            .wpfnl-checkout form .form-row .woocommerce-billing-fields select.select,
            .wpfnl-checkout form .woocommerce-billing-fields .form-row select' => array(
                'border-radius' => $attributes['billingInputsBorderRadius'].'px',
            ),
            //---end billing input fields----


            /* -------------------Shipping section style-------------------- */
            '.wpfnl-checkout .woocommerce-additional-fields h3,
            .wpfnl-checkout .woocommerce-shipping-fields #ship-to-different-address,
            .wpfnl-checkout .woocommerce-shipping-fields #ship-to-different-address label' => array(
                'color' => $attributes['shippingHeaderColor'],
            ),

            '.wpfnl-checkout .woocommerce-additional-fields h3,
            .wpfnl-checkout .woocommerce-shipping-fields #ship-to-different-address' => array(
                'margin-top' => $attributes['shippingHeaderMarginTop'].'px',
                'margin-right' => $attributes['shippingHeaderMarginRight'].'px',
                'margin-bottom' => $attributes['shippingHeaderMarginBottom'].'px',
                'margin-left' => $attributes['shippingHeaderMarginLeft'].'px',
                'padding-top' => $attributes['shippingHeaderPaddingTop'].'px',
                'padding-right' => $attributes['shippingHeaderPaddingRight'].'px',
                'padding-bottom' => $attributes['shippingHeaderPaddingBottom'].'px',
                'padding-left' => $attributes['shippingHeaderPaddingLeft'].'px',
            ),

            '.wpfnl-checkout .woocommerce-additional-fields .form-row > label,
            .wpfnl-checkout .woocommerce-shipping-fields .form-row > label' => array(
                'color' => $attributes['shippingLabelColor'],
                'margin-top' => $attributes['shippingLabelMarginTop'].'px',
                'margin-right' => $attributes['shippingLabelMarginRight'].'px',
                'margin-bottom' => $attributes['shippingLabelMarginBottom'].'px',
                'margin-left' => $attributes['shippingLabelMarginLeft'].'px',
            ),

            '.wpfnl-checkout .woocommerce-shipping-fields .form-row input.input-text,
            .wpfnl-checkout .woocommerce-additional-fields .form-row textarea,
            .wpfnl-checkout .woocommerce-shipping-fields .select2-container--default .select2-selection--single,
            .wpfnl-checkout .woocommerce-shipping-fields .form-row select.select,
            .wpfnl-checkout .woocommerce-shipping-fields .form-row select' => array(
                'color' => $attributes['shippingFieldTextColor'],
                'background-color' => $attributes['shippingFieldBackgroundColor'],
                'border-radius' => $attributes['shippingInputsBorderRadius'].'px',
            ),

            '.wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_shipping .form-row:not(.create-account) > label' => array(
                'background-color' => $attributes['shippingFieldBackgroundColor'],
            ),

            '.wpfnl-checkout .woocommerce-shipping-fields .form-row input.input-text,
            .wpfnl-checkout .woocommerce-additional-fields .form-row textarea,
            .wpfnl-checkout .woocommerce-shipping-fields .form-row select.select,
            .wpfnl-checkout .woocommerce-shipping-fields .form-row select' => array(
                'margin' => "".$attributes['shippingInputMarginTop']."px ".$attributes['shippingInputMarginRight']."px ".$attributes['shippingInputMarginBottom']."px ".$attributes['shippingInputMarginLeft']."px",
                'padding' => "".$attributes['shippingInputPaddingTop']."px ".$attributes['shippingInputPaddingRight']."px ".$attributes['shippingInputPaddingBottom']."px ".$attributes['shippingInputPaddingLeft']."px",
                'line-height' => '20px',
                'border-style' => $attributes['shippingInputBorderStyle'],
                'border-width' => "".$attributes['shippingInputBorderWidth']."px",
                'border-color' => $attributes['shippingInputBorderColor'],
            ),

            '.wpfnl-checkout .woocommerce-shipping-fields .select2-container--default .select2-selection--single' => array(
                'border-style' => $attributes['shippingInputBorderStyle'],
                'border-width' => "".$attributes['shippingInputBorderWidth']."px",
                'border-color' => $attributes['shippingInputBorderColor'],
            ),

            '.wpfnl-checkout .woocommerce .woocommerce-shipping-fields .form-row .select2-selection__rendered' => array(
                'color' => $attributes['shippingFieldTextColor'],
                'padding' => "".$attributes['shippingInputPaddingTop']."px ".$attributes['shippingInputPaddingRight']."px ".$attributes['shippingInputPaddingBottom']."px ".$attributes['shippingInputPaddingLeft']."px",
                'line-height' => '20px',
            ),

            '.wpfnl-checkout .woocommerce-shipping-fields .form-row input.input-text::placeholder,
            .wpfnl-checkout .woocommerce-additional-fields .form-row textarea::placeholder' => array(
                'color' => $attributes['shippingFieldTextColor']
            ),

            //-----payment method radio button color--------
            '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li > label:before,
            .wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li > label:before' => array(
                'border-color' => isset( $attributes['paymentRadioDefaultColor'] ) ? isset( $attributes['paymentRadioDefaultColor'] ) : ''
            ),

            '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li > input[type=radio]:checked + label:before,
            .wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li > input[type=radio]:checked + label:before' => array(
                'border-color' => isset( $attributes['paymentRadioColor'] ) ? $attributes['paymentRadioColor'] : ''
            ),
            '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li > input[type=radio]:checked + label:after,
            .wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li > input[type=radio]:checked + label:after' => array(
                'background-color' => isset( $attributes['paymentRadioColor'] ) ? $attributes['paymentRadioColor'] : ''
            ),

            //-----payment method checkbox color--------
            '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods-saveNew > label:before, 
            .wpfnl-checkout #mailpoet_woocommerce_checkout_optin_field #mailpoet_woocommerce_checkout_optin' => array(
                'border-color' => isset( $attributes['paymentCheckboxDefaultColor'] ) ? $attributes['paymentCheckboxDefaultColor'] : ''
            ),
            '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods-saveNew > input[type=checkbox]:checked + label:before, 
            .wpfnl-checkout #mailpoet_woocommerce_checkout_optin_field #mailpoet_woocommerce_checkout_optin:checked' => array(
                'border-color' => isset( $attributes['paymentCheckboxColor'] ) ? $attributes['paymentCheckboxColor'] : '',
                'background-color' => isset( $attributes['paymentCheckboxColor'] ) ? $attributes['paymentCheckboxColor'] : ''
            ),
            '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods-saveNew > label:after' => array(
                'border-color' => isset( $attributes['paymentCheckboxTicColor'] ) ? $attributes['paymentCheckboxTicColor'] : '',
            ),


            /* ----order section style----- */
            '.woocommerce-checkout .wpfnl-checkout form #order_review_heading,
            .wpfnl-express-checkout .wpfnl-express-checkout-wrapper .wpfnl-express-checkout-right #order_review_heading' => array(
                'color' => $attributes['orderHeaderColor'],
                'margin-top' => $attributes['orderHeaderMarginTop'].'px',
                'margin-right' => $attributes['orderHeaderMarginRight'].'px',
                'margin-bottom' => $attributes['orderHeaderMarginBottom'].'px',
                'margin-left' => $attributes['orderHeaderMarginLeft'].'px',
                'padding-top' => $attributes['orderHeaderPaddingTop'].'px',
                'padding-right' => $attributes['orderHeaderPaddingRight'].'px',
                'padding-bottom' => $attributes['orderHeaderPaddingBottom'].'px',
                'padding-left' => $attributes['orderHeaderPaddingLeft'].'px',
            ),

            '.wpfnl-checkout table.woocommerce-checkout-review-order-table.shop_table td,
            .wpfnl-checkout table.woocommerce-checkout-review-order-table.shop_table th,
            .wpfnl-checkout table.woocommerce-checkout-review-order-table.shop_table' => array(
                'border-color' => $attributes['orderTableBorderColor'].'!important',
            ),

            '.wpfnl-checkout table.woocommerce-checkout-review-order-table.shop_table td,
            .wpfnl-checkout table.woocommerce-checkout-review-order-table.shop_table th' => array(
                'color' => $attributes['orderTableTextColor'],
            ),


            /* ------payment section style----- */
            '.woocommerce-checkout .wpfnl-checkout #payment ul.payment_methods li label' => array(
                'color' => $attributes['paymentRadioBtnLabelColor'],
            ),

            '.woocommerce-checkout .wpfnl-checkout #payment p,
            .woocommerce-checkout .wpfnl-checkout #payment span' => array(
                'color' => $attributes['paymentSectionTextColor'],
            ),

            '.woocommerce-checkout .wpfnl-checkout #payment' => array(
                'background-color' => $attributes['paymentSectionBgColor'],
            ),

            '.woocommerce-checkout .wpfnl-checkout #payment a' => array(
                'color' => $attributes['paymentSectionLinkColor'],
            ),

            '.woocommerce-checkout .wpfnl-checkout #payment div.payment_box p' => array(
                'color' => $attributes['paymentBoxTextColor'],
            ),

            '.woocommerce-checkout .wpfnl-checkout #payment div.payment_box' => array(
                'background-color' => $attributes['paymentBoxBgColor'],
            ),

            '.woocommerce-checkout .wpfnl-checkout #payment div.payment_box::before' => array(
                'border-bottom-color' => $attributes['paymentBoxBgColor'],
            ),

            '.wpfnl-checkout .woocommerce #payment #place_order' => array(
                'background-color' => $attributes['orderButtonBgColor'],
                'color' => $attributes['orderButtonTextColor'],
            ),



            /* ----start multistep section style----- */
            '.wpfnl-multistep .wpfnl-multistep-wizard li .step-title' => array(
                'color' => $attributes['stepTitleColor']
            ),

            '.wpfnl-multistep .wpfnl-multistep-wizard:before' => array(
                'background-color' => $attributes['stepLineColor']
            ),

            '.wpfnl-multistep .wpfnl-multistep-wizard li .step-icon' => array(
                'background-color' => $attributes['boxBgColor'],
                'border-color' => $attributes['boxBorderColor'],
            ),
            '.wpfnl-multistep .wpfnl-multistep-wizard li .step-icon svg path' => array(
                'fill' => $attributes['stepIconColor'],
            ),

            '.wpfnl-multistep .wpfnl-multistep-wizard > li.completed:before,
            .wpfnl-multistep .wpfnl-multistep-wizard > li.current:before' => array(
                'background-color' => $attributes['stepLineActiveColor'],
            ),

            '.wpfnl-multistep .wpfnl-multistep-wizard li.completed .step-icon,
            .wpfnl-multistep .wpfnl-multistep-wizard li.current .step-icon' => array(
                'background-color' => $attributes['boxBgActiveColor'],
                'border-color' => $attributes['boxBorderActiveColor'],
            ),

            '.wpfnl-multistep .wpfnl-multistep-wizard li.completed .step-icon svg path,
            .wpfnl-multistep .wpfnl-multistep-wizard li.current .step-icon svg path' => array(
                'fill' => $attributes['stepIconActiveColor'],
            ),

            '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]' => array(
                'color' => $attributes['stepNavigationBtnColor']."!important",
                'background-color' => $attributes['stepNavigationBtnBgColor']."!important",
                'padding' => "".$attributes['stepNavigationBtnPaddingTop']."px ".$attributes['stepNavigationBtnPaddingRight']."px ".$attributes['stepNavigationBtnPaddingBottom']."px ".$attributes['stepNavigationBtnPaddingLeft']."px",
            ),

            '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]:not(:disabled):hover' => array(
                'color' => $attributes['stepNavigationBtnHvrColor']."!important",
                'background-color' => $attributes['stepNavigationBtnHvrBgColor']."!important",
            ),
            /* ----end multistep section style----- */

        );
        return $this->generate_css($selectors);
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
        $classes = array( 'wpfnl-block-' . $this->block_name );
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
     * Show checkout markup by ajax response
     *
     * @throws \Exception
     */
    public function show_checkout_markup() {

		$get_attributes = array(
			'billingHeaderColor'    => isset( $_POST['billingHeaderColor'] ) ? $_POST['billingHeaderColor'] : 'red',
			'layout'                => isset( $_POST['layout'] ) ? $_POST['layout'] : 'wpfnl-col-2',
			'placeOrderBtnText'     => isset( $_POST['placeOrderBtnText'] ) ? sanitize_text_field( $_POST['placeOrderBtnText'] ) : 'Place Order',
			'placeOrderSubText'     => isset( $_POST['placeOrderSubText'] ) ? sanitize_text_field( $_POST['placeOrderSubText'] ) : '',
			'placeOrderEnableIcon'  => isset( $_POST['placeOrderEnableIcon'] ) ? filter_var( $_POST['placeOrderEnableIcon'], FILTER_VALIDATE_BOOLEAN ) : false,
			'placeOrderIconStyle'   => isset( $_POST['placeOrderIconStyle'] ) ? sanitize_text_field( $_POST['placeOrderIconStyle'] ) : 'lock1',
			'placeOrderEnablePrice' => isset( $_POST['placeOrderEnablePrice'] ) ? filter_var( $_POST['placeOrderEnablePrice'], FILTER_VALIDATE_BOOLEAN ) : false,
			'placeOrderBelowText'   => isset( $_POST['placeOrderBelowText'] ) ? sanitize_text_field( $_POST['placeOrderBelowText'] ) : '',
		);
		$checkout_id				= isset($_POST['post_id']) ? $_POST['post_id'] : 0;

		$checkout_layout 	= isset($_POST['layout']) ? $_POST['layout'] : 'wpfnl-col-2';
		$floating_label 	= isset($_POST['floating_label']) ? $_POST['floating_label'] : '';

		if( PHP_SESSION_DISABLED == session_status() ) {
			session_start();
		}
		$_SESSION[ 'checkout_layout' ] = $checkout_layout;


        if( \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-express-checkout' === $checkout_layout ){
            $checkout_layout .= ' wpfnl-multistep';
        }
        
        if( \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-2-step' === $checkout_layout ){
            $checkout_layout .= ' wpfnl-multistep';
        }
        
        if( \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() && 'floating-label' === $floating_label ){
            $floating_label .= ' floating-label';
        }

       
		$attributes 		= wp_parse_args( $get_attributes, $this->defaults );

		$dynamic_css 		= $this->generate_assets( $attributes );
		do_action( 'wpfunnels/gutenberg_checkout_dynamic_filters', $attributes );
		do_action( 'wpfunnels/before_gb_checkout_form_ajax', $checkout_id, $_POST );

		$place_order_filter = $this->get_place_order_button_filter( $attributes );
		add_filter( 'woocommerce_order_button_html', $place_order_filter );

		$below_text = isset( $attributes['placeOrderBelowText'] ) ? $attributes['placeOrderBelowText'] : '';
		$below_btn_filter = null;
		if ( ! empty( $below_text ) ) {
			$below_btn_filter = function() use ( $below_text ) {
				echo '<div class="wpfnl-below-place-order-btn">' . wp_kses_post( $below_text ) . '</div>';
			};
			add_action( 'woocommerce_review_order_after_submit', $below_btn_filter );
		}

		$output  		= sprintf( '<div class="%1$s" style="%2$s">', esc_attr( $this->get_classes( $attributes ) ), esc_attr( $this->get_styles( $attributes ) ) );

		$output 		.= '<div class="wpfnl-block-checkout-form__wrapper wpfnl-checkout '.$checkout_layout.' '.$floating_label.'">';
		$output 		.= do_shortcode('[woocommerce_checkout]');
		$output 		.= '</div>';
		$output 		.= '</div>';
		$output 		.= "<style>$dynamic_css</style>";

		remove_filter( 'woocommerce_order_button_html', $place_order_filter );
		if ( null !== $below_btn_filter ) {
			remove_action( 'woocommerce_review_order_after_submit', $below_btn_filter );
		}

		wp_send_json_success($output);
    }
}
