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

        // Display Conditions
        'displayConditionType' => 'none',
        'hideFromLoggedIn'     => false,
        'hideFromLoggedOut'    => false,
        'hideForUserRole'      => 'none',
        'hideOnBrowser'        => 'none',
        'hideOnOS'             => 'none',
        'disableOnDays'        => array(),

        // Responsive Display
        'hideOnDesktop'        => false,
        'hideOnTablet'         => false,
        'hideOnMobile'         => false,

        // Animation
        'animation'            => '',
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
            'lock1'    => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" data-name="Layer 1" id="Layer_1" viewBox="0 0 128 128"><defs><style>.cls-1{fill:none;stroke:currentColor;stroke-linecap:round;stroke-linejoin:round;stroke-width:8px;}</style></defs><title/><path class="cls-1" d="M40,53V35A24,24,0,0,1,64,11h0A24,24,0,0,1,88,35V45a8,8,0,0,1-8,8H36a8,8,0,0,0-8,8v46a8,8,0,0,0,8,8H92a8,8,0,0,0,8-8"/><path class="cls-1" d="M80,53H92a8,8,0,0,1,8,8v46"/><circle fill="currentColor" cx="64" cy="82" r="8"/></svg>',

            'lock2'    => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 32 32"><g id="Layer_11" fill="currentColor" data-name="Layer 11"><path d="m24 11.57h-.53v-3.1a7.47 7.47 0 1 0 -14.94 0v3.1h-.53a4 4 0 0 0 -4 4v11.43a4 4 0 0 0 4 4h16a4 4 0 0 0 4-4v-11.43a4 4 0 0 0 -4-4zm-13.47-3.1a5.47 5.47 0 1 1 10.94 0v3.1h-10.94zm15.47 18.53a2 2 0 0 1 -2 2h-16a2 2 0 0 1 -2-2v-11.43a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2z"/><path d="m16 17.926a1.948 1.948 0 0 0 -1.354 3.348v2.371a1 1 0 0 0 1 1h.708a1 1 0 0 0 1-1v-2.371a1.948 1.948 0 0 0 -1.354-3.348z"/></g></svg>',

            'cart1'    => '<svg fill="currentColor" xmlns="http://www.w3.org/2000/svg" width="24" height="24" id="Layer_1" enable-background="new 0 0 64 64" height="512" viewBox="0 0 64 64" width="512"><g><g><path d="m25.308 61.679c-3.514 0-6.373-2.859-6.373-6.373s2.859-6.372 6.373-6.372 6.373 2.858 6.373 6.372-2.859 6.373-6.373 6.373zm0-8.745c-1.308 0-2.373 1.064-2.373 2.372 0 1.309 1.064 2.373 2.373 2.373s2.373-1.064 2.373-2.373c0-1.308-1.064-2.372-2.373-2.372z"/><path d="m47.462 61.679c-3.514 0-6.372-2.859-6.372-6.373s2.858-6.372 6.372-6.372 6.373 2.858 6.373 6.372-2.86 6.373-6.373 6.373zm0-8.745c-1.308 0-2.372 1.064-2.372 2.372 0 1.309 1.064 2.373 2.372 2.373 1.309 0 2.373-1.064 2.373-2.373 0-1.308-1.065-2.372-2.373-2.372z"/></g><path d="m52.128 43.994h-31.419c-3.057 0-5.668-2.081-6.35-5.061l-6.521-28.477c-.086-.376-.346-.698-.696-.86l-5.409-2.507c-1.252-.58-1.797-2.066-1.217-3.319.58-1.252 2.067-1.797 3.319-1.217l5.409 2.507c1.743.807 3.04 2.407 3.468 4.28l6.521 28.479c.158.692.765 1.176 1.476 1.176h31.419c.708 0 1.314-.481 1.476-1.171l5.07-21.813c.145-.62-.119-1.071-.288-1.284-.17-.214-.55-.572-1.186-.572h-37c-1.381 0-2.5-1.119-2.5-2.5s1.119-2.5 2.5-2.5h37c1.999 0 3.857.897 5.101 2.462s1.696 3.579 1.244 5.526l-5.071 21.814c-.691 2.965-3.3 5.037-6.346 5.037z"/></g></svg>',

            'checkout' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" xmlns:svg="http://www.w3.org/2000/svg" version="1.1" id="svg2001" xml:space="preserve" width="682.66669" height="682.66669" viewBox="0 0 682.66669 682.66669"><defs id="defs2005"><clipPath clipPathUnits="userSpaceOnUse" id="clipPath2015"><path d="M 0,512 H 512 V 0 H 0 Z" id="path2013"/></clipPath></defs><g id="g2007" transform="matrix(1.3333333,0,0,-1.3333333,0,682.66667)"><g id="g2009"><g id="g2011" clip-path="url(#clipPath2015)"><g id="g2017" transform="translate(106,406)"><path d="M 0,0 H 391 L 331,-210 H 60" style="fill:none;stroke:currentColor;stroke-width:30;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1" id="path2019"/></g><g id="g2021" transform="translate(256,76)"><path d="m 0,0 c 0,-16.568 -13.432,-30 -30,-30 -16.568,0 -30,13.432 -30,30 0,16.568 13.432,30 30,30 C -13.432,30 0,16.568 0,0 Z" style="fill:none;stroke:currentColor;stroke-width:30;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1" id="path2023"/></g><g id="g2025" transform="translate(407,76)"><path d="m 0,0 c 0,-16.568 -13.432,-30 -30,-30 -16.568,0 -30,13.432 -30,30 0,16.568 13.432,30 30,30 C -13.432,30 0,16.568 0,0 Z" style="fill:none;stroke:currentColor;stroke-width:30;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1" id="path2027"/></g><g id="g2029" transform="translate(437,136)"><path d="m 0,0 h -252.459 c -22.301,0 -36.806,23.469 -26.833,43.417 L -271,60" style="fill:none;stroke:currentColor;stroke-width:30;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1" id="path2031"/></g><g id="g2033" transform="translate(15,466)"><path d="M 0,0 H 73.861 C 99.402,-89.409 151,-270 151,-270" style="fill:none;stroke:currentColor;stroke-width:30;stroke-linecap:round;stroke-linejoin:round;stroke-miterlimit:10;stroke-dasharray:none;stroke-opacity:1" id="path2035"/></g></g></g></g></svg>',
            'arrow1'   => '<svg width="24" height="24 xmlns="http://www.w3.org/2000/svg" fill="none" height="100" viewBox="0 0 100 100" width="100"><path clip-rule="evenodd" d="m51.2204 22.0537c1.6271-1.6272 4.2653-1.6272 5.8925 0l25 25c1.6272 1.6272 1.6272 4.2654 0 5.8926l-25 25c-1.6272 1.6271-4.2654 1.6271-5.8925 0-1.6272-1.6272-1.6272-4.2654 0-5.8926l17.887-17.8871h-48.2741c-2.3012 0-4.1667-1.8654-4.1667-4.1666s1.8655-4.1667 4.1667-4.1667h48.2741l-17.887-17.887c-1.6272-1.6272-1.6272-4.2654 0-5.8926z" fill="currentColor" fill-rule="evenodd"/></svg>',
            'arrow2'   => '<svg fill="currentColor" width="24" height="24" xmlns="http://www.w3.org/2000/svg" height="512" viewBox="0 0 24 24" width="512"><g id="_19" data-name="19"><path d="m12 19a1 1 0 0 1 -.71-1.71l5.3-5.29-5.3-5.29a1 1 0 0 1 1.41-1.41l6 6a1 1 0 0 1 0 1.41l-6 6a1 1 0 0 1 -.7.29z"/><path d="m6 19a1 1 0 0 1 -.71-1.71l5.3-5.29-5.3-5.29a1 1 0 0 1 1.42-1.42l6 6a1 1 0 0 1 0 1.41l-6 6a1 1 0 0 1 -.71.3z"/></g></svg>',
            'arrow3'   => '<svg fill="currentColor" width="24" height="24" xmlns="http://www.w3.org/2000/svg" height="512" viewBox="0 0 24 24" width="512"><g id="_15" data-name="15"><path d="m9 19a1 1 0 0 1 -.71-1.71l5.3-5.29-5.3-5.29a1 1 0 0 1 1.42-1.42l6 6a1 1 0 0 1 0 1.41l-6 6a1 1 0 0 1 -.71.3z"/></g></svg>',
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

        // Check display conditions
        $display_condition_type = isset( $attributes['displayConditionType'] ) ? $attributes['displayConditionType'] : 'none';
        
        if ( 'user_state' === $display_condition_type ) {
            $hide_from_logged_in = isset( $attributes['hideFromLoggedIn'] ) ? $attributes['hideFromLoggedIn'] : false;
            $hide_from_logged_out = isset( $attributes['hideFromLoggedOut'] ) ? $attributes['hideFromLoggedOut'] : false;
            
            if ( $hide_from_logged_in && is_user_logged_in() ) {
                return '';
            }
            if ( $hide_from_logged_out && ! is_user_logged_in() ) {
                return '';
            }
        } elseif ( 'user_role' === $display_condition_type ) {
            $hide_for_user_role = isset( $attributes['hideForUserRole'] ) ? $attributes['hideForUserRole'] : 'none';
            if ( 'none' !== $hide_for_user_role && is_user_logged_in() ) {
                $user = wp_get_current_user();
                if ( in_array( $hide_for_user_role, (array) $user->roles ) ) {
                    return '';
                }
            }
        } elseif ( 'browser' === $display_condition_type ) {
            $hide_on_browser = isset( $attributes['hideOnBrowser'] ) ? $attributes['hideOnBrowser'] : 'none';
            if ( 'none' !== $hide_on_browser ) {
                $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
                $browser_match = false;
                
                if ( 'mozilla' === $hide_on_browser && stripos( $user_agent, 'Firefox' ) !== false ) {
                    $browser_match = true;
                } elseif ( 'chrome' === $hide_on_browser && stripos( $user_agent, 'Chrome' ) !== false && stripos( $user_agent, 'Edg' ) === false ) {
                    $browser_match = true;
                } elseif ( 'opera_mini' === $hide_on_browser && stripos( $user_agent, 'Opera Mini' ) !== false ) {
                    $browser_match = true;
                } elseif ( 'safari' === $hide_on_browser && stripos( $user_agent, 'Safari' ) !== false && stripos( $user_agent, 'Chrome' ) === false ) {
                    $browser_match = true;
                } elseif ( 'edge' === $hide_on_browser && stripos( $user_agent, 'Edg' ) !== false ) {
                    $browser_match = true;
                }
                
                if ( $browser_match ) {
                    return '';
                }
            }
        } elseif ( 'operating_system' === $display_condition_type ) {
            $hide_on_os = isset( $attributes['hideOnOS'] ) ? $attributes['hideOnOS'] : 'none';
            if ( 'none' !== $hide_on_os ) {
                $user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '';
                $os_match = false;
                
                if ( 'ios' === $hide_on_os && ( stripos( $user_agent, 'iPhone' ) !== false || stripos( $user_agent, 'iPad' ) !== false ) ) {
                    $os_match = true;
                } elseif ( 'android' === $hide_on_os && stripos( $user_agent, 'Android' ) !== false ) {
                    $os_match = true;
                } elseif ( 'windows' === $hide_on_os && stripos( $user_agent, 'Windows' ) !== false ) {
                    $os_match = true;
                } elseif ( 'macos' === $hide_on_os && stripos( $user_agent, 'Macintosh' ) !== false ) {
                    $os_match = true;
                } elseif ( 'linux' === $hide_on_os && stripos( $user_agent, 'Linux' ) !== false && stripos( $user_agent, 'Android' ) === false ) {
                    $os_match = true;
                } elseif ( 'sunos' === $hide_on_os && stripos( $user_agent, 'SunOS' ) !== false ) {
                    $os_match = true;
                } elseif ( 'openbsd' === $hide_on_os && stripos( $user_agent, 'OpenBSD' ) !== false ) {
                    $os_match = true;
                }
                
                if ( $os_match ) {
                    return '';
                }
            }
        } elseif ( 'day' === $display_condition_type ) {
            $disable_on_days = isset( $attributes['disableOnDays'] ) ? $attributes['disableOnDays'] : array();
            if ( ! empty( $disable_on_days ) && is_array( $disable_on_days ) ) {
                $current_day = strtolower( date( 'l' ) );
                if ( in_array( $current_day, $disable_on_days ) ) {
                    return '';
                }
            }
        }

        // Build responsive classes
        $responsive_classes = array();
        if ( isset( $attributes['hideOnDesktop'] ) && $attributes['hideOnDesktop'] ) {
            $responsive_classes[] = 'wpfnl-hide-desktop';
        }
        if ( isset( $attributes['hideOnTablet'] ) && $attributes['hideOnTablet'] ) {
            $responsive_classes[] = 'wpfnl-hide-tablet';
        }
        if ( isset( $attributes['hideOnMobile'] ) && $attributes['hideOnMobile'] ) {
            $responsive_classes[] = 'wpfnl-hide-mobile';
        }

        // Build animation classes
        $animation_classes = array();
        if ( isset( $attributes['animation'] ) && ! empty( $attributes['animation'] ) ) {
            $animation_classes[] = 'wpfnl-animation';
            $animation_classes[] = '' . esc_attr( $attributes['animation'] );
        }

        // Combine all additional classes
        $additional_classes = array_merge( $responsive_classes, $animation_classes );
        $additional_classes_str = ! empty( $additional_classes ) ? ' ' . implode( ' ', $additional_classes ) : '';

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

        $output  = sprintf( '<div class="%1s%2s" style="%3s">', esc_attr( $this->get_classes( $attributes ) ), $additional_classes_str, esc_attr( $this->get_styles( $attributes ) ) );
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

		if('wpfnl-modern-multistep' === $checkout_layout ){
            $checkout_layout .= ' wpfnl-modern-checkout';
        }

		if( 'wpfnl-modern-one-column' === $checkout_layout ){
            $checkout_layout .= ' wpfnl-modern-checkout';
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
