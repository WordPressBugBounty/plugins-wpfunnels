<?php
/**
 * Checkout
 *
 * @package
 */
namespace WPFunnels\Widgets\Oxygen;

use WPFunnels\Wpfnl_functions;

/**
 * Class Checkout
 *
 * @package WPFunnels\Widgets\Oxygen
 */
class Checkout extends Elements {

    function init() {
        // Do some initial things here.
    }

    function afterInit() {
        // Do things after init, like remove apply params button and remove the add button.
        $this->removeApplyParamsButton();
        // $this->removeAddButton();
    }

    function name() {
        return 'WPF Checkout';
    }

    function slug() {
        return "wpf-checkout";
    }

    function icon() {
        return	plugin_dir_url(__FILE__) . 'icon/checkout.svg';
    }

    //    function button_place() {
    //        // return "interactive";
    //    }

    function button_priority() {
        // return 9;
    }


    function render( $options, $defaults, $content ) {
        if( !Wpfnl_functions::check_if_this_is_step_type( 'checkout' ) ) {
            echo __( 'Sorry, Please place the element in WPFunnels Checkout page','wpfnl' );
        }
        else {
            $step_id         = isset( $_GET[ 'post_id' ] ) ? $_GET[ 'post_id' ] : get_the_ID();
            $checkout_layout = isset( $options[ 'layout' ] ) ? $options[ 'layout' ] : '';

            update_post_meta( $step_id, '_wpfnl_checkout_layout', $checkout_layout );

            add_filter( 'woocommerce_locate_template', array( $this, 'wpfunnels_woocommerce_locate_template' ), 20, 3 );
            do_action( 'wpfunnels/before_checkout_form', $step_id );

            if( PHP_SESSION_DISABLED == session_status() ) {
                session_start();
            }
            $_SESSION[ 'checkout_layout' ] = $checkout_layout;

            if( \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-express-checkout' === $checkout_layout ) {
                $checkout_layout .= ' wpfnl-multistep';
            }

            if( \WPFunnels\Wpfnl_functions::is_wpfnl_pro_activated() && 'wpfnl-2-step' === $checkout_layout ) {
                $checkout_layout .= ' wpfnl-multistep';
            }

            //-----floating label class-----
            $floating_label = isset($options['checkout_floating_label']) ? $options['checkout_floating_label'] : '';

            $html = '<div class="wpfnl-checkout ' . $checkout_layout .' '. $floating_label .'" >';
            $html .= do_shortcode( '[woocommerce_checkout]' );
            $html .= '</div>';

            echo $html;
        }
    }
    public static function wpfunnels_woocommerce_locate_template($template, $template_name, $template_path)
    {
        /***
		 * Fires when change the wc template
		 *
		 * @since 2.8.21
		 */
        if( apply_filters( 'wpfunnels/maybe_locate_template', true ) ){
            global $woocommerce;
            $_template 		= $template;
            $plugin_path 	= WPFNL_DIR . '/woocommerce/templates/';

            if (file_exists($plugin_path . $template_name)) {
                $template = $plugin_path . $template_name;
            }

            if ( ! $template ) {
                $template = $_template;
            }
        }
        return $template;
    }

    /**
     * Register widget controls.
     *
     * @since 2.8.2
     */
    public function controls() {
        $this->configure_layout_section();
        $this->configure_billing_section();
        $this->configure_shipping_section();
        $this->configure_order_section_style();
        $this->configure_table_style();
        $this->configure_payment_section_style();
        $this->configure_order_button_style();
        $this->configure_coupon_section_style();
        $this->configure_multistep_style();
    }

    /**
     * Configure the layout section settings for the checkout page.
     *
     * @since 2.8.2
     */
    private function configure_layout_section() {
        // Add a control section for layout settings
        $layout = $this->addControlSection(
            'optin_layout',
            __('Layout', 'wpfnl'),
            'assets/icon.png',
            $this
        );

        // Add a dropdown option control for selecting layout
        $layout->addOptionControl(
            array(
                'type' => 'dropdown',
                'name' => __('Layout', 'wpfnl'),
                'slug' => 'layout',
                'default' => 'wpfnl-col-2'
            )
        )->setValue(
            array(
                'wpfnl-col-1' => __('1 Column Checkout', 'wpfnl'),
                'wpfnl-col-2' => __('2 Column Checkout', 'wpfnl'),
                'wpfnl-2-step' => __('2 Step Checkout', 'wpfnl'),
                'wpfnl-multistep' => __('Multistep Checkout', 'wpfnl'),
                'wpfnl-express-checkout' => __('Express Checkout', 'wpfnl'),
            )
        )->rebuildElementOnChange();

        // Add a dropdown option control for floating label selection
        $layout->addOptionControl(
            array(
                'type' => 'dropdown',
                'name' => __('Floating Label', 'wpfnl'),
                'slug' => 'checkout_floating_label',
                'default' => ''
            )
        )->setValue(
            array(
                '' => __('Select option', 'wpfnl'),
                'floating-label' => __('Floating Label', 'wpfnl'),
            )
        )->rebuildElementOnChange();
    }

    /**
     * Configure the billing section styles for the checkout page.
     *
     * @since 2.8.2
     */
    private function configure_billing_section() {
        // Configure Billing Header Style
        $this->configure_header_style(
            'billing_header',
            __('Billing Heading', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-billing-fields > h3'
        );

        // Configure Billing Label Style
        $this->configure_label_and_input_style(
            'billing_label',
            __('Billing Input Label', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-billing-fields p.form-row label',
            'Label Margin'
        );

        // Configure Billing Input Style
        $this->configure_label_and_input_style(
            'billing_inputs',
            __('Billing Input Field', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-billing-fields .form-row input.input-text, .wpfnl-checkout .woocommerce-billing-fields .form-row textarea, .wpfnl-checkout .woocommerce-billing-fields .select2-container--default .select2-selection--single, .wpfnl-checkout .woocommerce-billing-fields .form-row select, .wpfnl-checkout .woocommerce-billing-fields ::placeholder, .wpfnl-checkout .woocommerce-billing-fields ::-webkit-input-placeholder, .wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_billing .form-row:not(.create-account) input.input-text:not(#billing_address_2, #shipping_address_2), .wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_billing .form-row:not(.create-account) textarea',
            'Input Margin'
        );
    }

    /**
     * Configure the header style for a specific section on the checkout page.
     *
     * @param string $sectionId The ID of the section.
     * @param string $sectionTitle The title of the section.
     * @param string $selector The CSS selector for the section.
     *
     * @since 2.8.2
     */
    private function configure_header_style($sectionId, $sectionTitle, $selector) {
        $section = $this->addControlSection($sectionId, $sectionTitle, 'assets/icon.png', $this);
        $section->typographySection(__('Heading Typography', 'wpfnl'), $selector, $this);
        $this->configure_common_style($section, $selector, 'Heading Border');
    }

    /**
     * Configure the label and input style for a specific section on the checkout page.
     *
     * @param string $sectionId The ID of the section.
     * @param string $sectionTitle The title of the section.
     * @param string $selector The CSS selector for the section.
     * @param string $marginControlName The name of the margin control.
     *
     * @since 2.8.2
     */
    private function configure_label_and_input_style($sectionId, $sectionTitle, $selector, $marginControlName) {
        $section = $this->addControlSection($sectionId, $sectionTitle, 'assets/icon.png', $this);
        $section->addPreset('margin', "{$sectionId}_margin", __($marginControlName, 'wpfnl'), $selector)->whiteList();
        $section->typographySection(__('Label Typography', 'wpfnl'), $selector, $this);
        $this->configure_common_style($section, $selector, 'Input Border');
    }

    /**
     * Configure common style settings for a specific section on the checkout page.
     *
     * @param object $section The section to configure.
     * @param string $selector The CSS selector for the section.
     * @param string $borderSectionTitle The title for the border section.
     *
     * @since 2.8.2
     */
    private function configure_common_style($section, $selector, $borderSectionTitle) {
        $section->addPreset('padding', "{$section->id}_padding", __('Padding', 'wpfnl'), $selector)->whiteList();
        $section->addStyleControls([
            [
                'name' => __('Background Color', 'wpfnl'),
                'selector' => $selector,
                'property' => 'background-color',
            ],
        ]);
        $section->borderSection($borderSectionTitle, $selector, $this);
    }

    /**
     * Configure the styling for the shipping section on the checkout page.
     *
     * @since 2.8.2
     */
    private function configure_shipping_section() {
        // Configure Shipping Header Style
        $this->configure_header_style(
            'shipping_header',
            __('Shipping Heading', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-shipping-fields #ship-to-different-address span, .wpfnl-checkout .woocommerce-additional-fields > h3'
        );

        // Configure Shipping Label Style
        $this->configure_label_and_input_style(
            'shipping_label',
            __('Shipping Input Label', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-shipping-fields p.form-row label, .wpfnl-checkout .woocommerce-additional-fields p.form-row label',
            'Label Margin'
        );

        // Configure Shipping Input Style
        $this->configure_label_and_input_style(
            'shipping_inputs',
            __('Shipping Input Field', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-shipping-fields .form-row input.input-text, .wpfnl-checkout .woocommerce-additional-fields .form-row textarea, .wpfnl-checkout .woocommerce-shipping-fields .select2-container--default .select2-selection--single, .wpfnl-checkout .woocommerce-shipping-fields .form-row select, .wpfnl-checkout .woocommerce-shipping-fields ::placeholder, .wpfnl-checkout .woocommerce-shipping-fields ::-webkit-input-placeholder, .wpfnl-checkout .woocommerce-additional-fields ::placeholder, .wpfnl-checkout .woocommerce-additional-fields ::-webkit-input-placeholder, .wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_shipping .form-row:not(.create-account) input.input-text:not(#billing_address_2, #shipping_address_2), .wpfnl-checkout.floating-label #customer_details #wpfnl_checkout_shipping .form-row:not(.create-account) textarea',
            'Input Margin'
        );
    }

    /**
     * Configure the styling for the order section heading on the checkout page.
     *
     * @since 2.8.2
     */
    private function configure_order_section_style() {
        // Add Control Section for Order Header
        $order_header = $this->addControlSection('order_header', __('Order Heading','wpfnl'), 'assets/icon.png', $this);

        // Selector for Order Header
        $order_header_selector = '.wpfnl-checkout .woocommerce-checkout #order_review_heading';

        // Configure Typography for Order Header
        $order_header->typographySection(
            __('Heading Typography','wpfnl'),
            $order_header_selector,
            $this
        );

        // Configure Padding Preset for Order Header
        $order_header->addPreset(
            'padding',
            'order_header_padding',
            __('Padding','wpfnl'),
            $order_header_selector
        )->whiteList();

        // Configure Margin Preset for Order Header
        $order_header->addPreset(
            'margin',
            'order_header_margin',
            __('Margin','wpfnl'),
            $order_header_selector
        )->whiteList();

        // Configure Background Color Style for Order Header
        $order_header->addStyleControls([
            [
                'name' => __('Background Color','wpfnl'),
                'selector' => $order_header_selector,
                'property' => 'background-color',
            ],
        ]);

        // Configure Border Section for Order Header
        $order_header->borderSection(
            __('Heading Border','wpfnl'),
            $order_header_selector,
            $this
        );
    }

    /**
     * Configure the styling for the order table on the checkout page.
     *
     * @since 2.8.2
     */
    private function configure_table_style() {
        // Add Control Section for Order Table.
        $order_table_section = $this->addControlSection('order_table_section', __('Order Table','wpfnl'), 'assets/icon.png', $this);

        // Selector for Order Table cells
        $order_table = '.wpfnl-checkout .woocommerce-checkout table.woocommerce-checkout-review-order-table td, .wpfnl-checkout .woocommerce-checkout table.woocommerce-checkout-review-order-table th';

        // Configure Margin Preset for Order Table.
        $order_table_section->addPreset(
            'margin',
            'order_table_margin',
            __('Table Margin','wpfnl'),
            '.wpfnl-checkout .woocommerce table.shop_table'
        )->whiteList();

        // Configure Padding Preset for Order Table Cells.
        $order_table_section->addPreset(
            'padding',
            'order_tbl_cell_padding',
            __('Cell Padding','wpfnl'),
            '.wpfnl-checkout .woocommerce table.shop_table thead th, .wpfnl-checkout .woocommerce table.shop_table thead td, .wpfnl-checkout .woocommerce table.shop_table tbody th, .wpfnl-checkout .woocommerce table.shop_table tbody td, .wpfnl-checkout .woocommerce table.shop_table tfoot td, .wpfnl-checkout .woocommerce table.shop_table tfoot th'
        )->whiteList();

        // Configure Background Color Style for Order Table.
        $order_table_section->addStyleControls([
            [
                'name' => __('Background Color','wpfnl'),
                'selector' => '.wpfnl-checkout .woocommerce table.shop_table',
                'property' => 'background-color',
            ],
        ]);

        // Configure Typography for Order Table Cells.
        $order_table_section->typographySection(
            __('Table Typography','wpfnl'),
            $order_table,
            $this
        );

        // Configure Border Section for Order Table.
        $order_table_section->borderSection(
            __('Table Border','wpfnl'),
            '.wpfnl-checkout .woocommerce table.shop_table',
            $this
        );
    }

    /**
     * Configure the styling for the payment section on the checkout page.
     *
     * @since 2.8.2
     */
    private function configure_payment_section_style() {
        // Add Control Section for Payment Section.
        $payment_section = $this->addControlSection('payment_section', __('Payment Section', 'wpfnl'), 'assets/icon.png', $this);

        // Configure Background Color Style for Payment Section.
        $payment_section->addStyleControls([
            [
                'name' => __('Payment Section Background Color', 'wpfnl'),
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment',
                'property' => 'background-color',
            ],
        ]);

        // Configure Link Color Style for Payment Section.
        $payment_section->addStyleControls([
            [
                'name' => __('Payment Section Link Color', 'wpfnl'),
                'selector' => '.wpfnl-checkout #payment .place-order a, .wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li > label a, .wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li > label a',
                'property' => 'color',
            ],
        ]);

        // Configure Typography for Payment Section.
        $payment_section->typographySection(
            __('Payment Section Typography', 'wpfnl'),
            '.wpfnl-checkout #payment .place-order p, .woocommerce-terms-and-conditions-wrapper .woocommerce-form__label-for-checkbox .woocommerce-terms-and-conditions-checkbox-text',
            $this
        );

        // Configure Border Section for Payment Section.
        $payment_section->borderSection(
            __('Payment Section Border', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-checkout #payment',
            $this
        );

        // Add Style Controls for Payment Method Typography.
        $payment_section->typographySection(
            __('Payment Method Typography', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li > label, .wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li > label',
            $this
        );

        // Configure Border Section for Payment Method.
        $payment_section->borderSection(
            __('Payment Method Border', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li, .wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li',
            $this
        );

        // Add Preset for Payment Method Padding.
        $payment_section->addPreset(
            'padding',
            'payment_method_padding',
            __('Payment Method Padding', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li, .wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li'
        )->whiteList();

        // Add Style Controls for Payment Method Tooltip.
        $payment_section->addStyleControls([
            [
                'name' => __('Payment Method Tooltip Background Color', 'wpfnl'),
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box',
                'property' => 'background-color',
            ],
            [
                'name' => __('Payment Method Tooltip Border Color', 'wpfnl'),
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box:before',
                'property' => 'border-bottom-color',
            ],
        ]);

        // Add Preset for Payment Method Tooltip Padding.
        $payment_section->addPreset(
            'padding',
            'payment_method_tooltip_padding',
            __('Payment Method Tooltip Padding', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box'
        )->whiteList();

        // Add Style Controls for Payment Method Radio Button and Checkbox.
        $payment_section->addStyleControls([
            [
                'name' => __('Radio Button Default Color', 'wpfnl'),
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li > label:before, .wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li > label:before',
                'property' => 'border-color',
            ],
            [
                'name' => __('Radio Button Active Color', 'wpfnl'),
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li > input[type=radio]:checked + label:before, .wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li > input[type=radio]:checked + label:before',
                'property' => 'border-color',
            ],
            [
                'name' => __('Radio Button Active Background Color', 'wpfnl'),
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods > li > input[type=radio]:checked + label:after, .wpfnl-checkout .woocommerce-checkout #payment ul.payment_methods > li > input[type=radio]:checked + label:after',
                'property' => 'background-color',
            ],
            [
                'name' => __('Checkbox Default Color', 'wpfnl'),
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods-saveNew > label:before, .wpfnl-checkout #mailpoet_woocommerce_checkout_optin_field #mailpoet_woocommerce_checkout_optin',
                'property' => 'border-color',
            ],
            [
                'name' => __('Checkbox Active Color', 'wpfnl'),
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods-saveNew > input[type=checkbox]:checked + label:before, .wpfnl-checkout #mailpoet_woocommerce_checkout_optin_field #mailpoet_woocommerce_checkout_optin:checked',
                'property' => 'border-color',
            ],
            [
                'name' => __('Checkbox Active Background Color', 'wpfnl'),
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods-saveNew > input[type=checkbox]:checked + label:before, .wpfnl-checkout #mailpoet_woocommerce_checkout_optin_field #mailpoet_woocommerce_checkout_optin:checked',
                'property' => 'background-color',
            ],
            [
                'name' => __('Checkbox Tic Sign Color', 'wpfnl'),
                'selector' => '.wpfnl-checkout .woocommerce-checkout #payment .woocommerce-SavedPaymentMethods-saveNew > label:after',
                'property' => 'border-color',
            ],
        ]);

        // Configure Typography for Payment Method Tooltip.
        $payment_section->typographySection(
            __('Payment Method Tooltip Typography', 'wpfnl'),
            '.wpfnl-checkout .woocommerce-checkout #payment div.payment_box, .wpfnl-checkout .woocommerce-checkout #payment div.payment_box p',
            $this
        );
    }

    /**
     * Configure the styling for the order button on the checkout page.
     *
     * @since 2.8.2
     */
    private function configure_order_button_style() {
        // Add Control Section for Order Button Section.
        $order_btn_section = $this->addControlSection('order_btn_section', __('Order Button Section', 'wpfnl'), 'assets/icon.png', $this);
        $order_btn = '.wpfnl-checkout .woocommerce #payment #place_order';

        // Configure Padding for Order Button.
        $order_btn_section->addPreset('padding', 'order_btn_padding', __('Padding', 'wpfnl'), $order_btn)->whiteList();

        // Configure Margin for Order Button.
        $order_btn_section->addPreset('margin', 'order_btn_margin', __('Margin', 'wpfnl'), $order_btn)->whiteList();

        // Configure Typography for Order Button.
        $order_btn_section->typographySection(__('Typography', 'wpfnl'), $order_btn, $this);

        // Configure Background Color for Order Button.
        $order_btn_section->addStyleControls([
            [
                'name' => __('Background Color', 'wpfnl'),
                'selector' => $order_btn,
                'property' => 'background-color',
            ],
        ]);

        // Configure Background Hover Color for Order Button.
        $order_btn_section->addStyleControls([
            [
                'name' => __('Background Hover Color', 'wpfnl'),
                'selector' => $order_btn . ':hover',
                'property' => 'background-color',
            ],
        ]);

        // Configure Text Hover Color for Order Button.
        $order_btn_section->addStyleControls([
            [
                'name' => __('Text Hover Color', 'wpfnl'),
                'selector' => $order_btn . ':hover',
                'property' => 'color',
            ],
        ]);

        // Configure Border Section for Order Button.
        $order_btn_section->borderSection(__('Border', 'wpfnl'), $order_btn, $this);
    }

    /**
     * Configure the styling for the coupon section on the checkout page.
     *
     * @since 2.8.2
     */
    private function configure_coupon_section_style() {
        // Add Control Section for Coupon Section.
        $coupon_section = $this->addControlSection('coupon_section', __('Coupon Section', 'wpfnl'), 'assets/icon.png', $this);
        $coupon_toggle_box = '.wpfnl-checkout .woocommerce-form-coupon-toggle .woocommerce-info';

        // Configure Padding for Coupon Toggle Box.
        $coupon_section->addPreset('padding', 'coupon_toggle_box_padding', __('Coupon Toggle Box Padding', 'wpfnl'), $coupon_toggle_box)->whiteList();

        // Configure Margin for Coupon Toggle Box.
        $coupon_section->addPreset('margin', 'coupon_toggle_box_margin', __('Coupon Toggle Box Margin', 'wpfnl'), $coupon_toggle_box)->whiteList();

        // Configure Background Color for Coupon Toggle Box.
        $coupon_section->addStyleControls([
            [
                'name' => __('Coupon Toggle Box Background Color', 'wpfnl'),
                'selector' => $coupon_toggle_box,
                'property' => 'background-color',
            ],
        ]);

        // Configure Link Color for Coupon Toggle Box.
        $coupon_section->addStyleControls([
            [
                'name' => __('Coupon Toggle Box Link Color', 'wpfnl'),
                'selector' => $coupon_toggle_box . ' a',
                'property' => 'color',
            ],
        ]);

        // Configure Typography for Coupon Toggle Box.
        $coupon_section->typographySection(__('Coupon Toggle Box Typography', 'wpfnl'), $coupon_toggle_box, $this);

        // Configure Border Section for Coupon Toggle Box.
        $coupon_section->borderSection(__('Coupon Toggle Box Border', 'wpfnl'), $coupon_toggle_box, $this);

        // Configure Coupon Form Style.
        $coupon_form = '.wpfnl-checkout .checkout_coupon.woocommerce-form-coupon';

        // Configure Padding for Coupon Form.
        $coupon_section->addPreset('padding', 'coupon_form_padding', __('Coupon Form Padding', 'wpfnl'), $coupon_form)->whiteList();

        // Configure Margin for Coupon Form.
        $coupon_section->addPreset('margin', 'coupon_form_margin', __('Coupon Form Margin', 'wpfnl'), $coupon_form)->whiteList();

        // Configure Background Color for Coupon Form.
        $coupon_section->addStyleControls([
            [
                'name' => __('Coupon Form Background Color', 'wpfnl'),
                'selector' => $coupon_form,
                'property' => 'background-color',
            ],
        ]);

        // Configure Typography for Coupon Form.
        $coupon_section->typographySection(__('Coupon Form Typography', 'wpfnl'), $coupon_form . ' p:not(.form-row)', $this);

        // Configure Border Section for Coupon Form.
        $coupon_section->borderSection(__('Coupon Form Border', 'wpfnl'), $coupon_form, $this);

        // Configure Coupon Input Field Style.
        $coupon_input_field = '.wpfnl-checkout .checkout_coupon.woocommerce-form-coupon input.input-text';

        // Configure Typography for Coupon Input Field.
        $coupon_section->typographySection(__('Coupon Form Input Field Typography', 'wpfnl'), $coupon_input_field, $this);

        // Configure Background Color for Coupon Input Field.
        $coupon_section->addStyleControls([
            [
                'name' => __('Coupon Form Input Field Background Color', 'wpfnl'),
                'selector' => $coupon_input_field,
                'property' => 'background-color',
            ],
        ]);

        // Configure Border Section for Coupon Input Field.
        $coupon_section->borderSection(__('Coupon Form Input Field Border', 'wpfnl'), $coupon_input_field, $this);

        // Configure Padding for Coupon Input Field.
        $coupon_section->addPreset('padding', 'coupon_input_field_padding', __('Coupon Form Input Field Padding', 'wpfnl'), $coupon_input_field)->whiteList();

        // Configure Coupon Button Style.
        $coupon_btn = '.wpfnl-checkout .checkout_coupon.woocommerce-form-coupon button[type=submit]';

        // Configure Padding for Coupon Button.
        $coupon_section->addPreset('padding', 'coupon_btn_padding', __('Coupon Button Padding', 'wpfnl'), $coupon_btn)->whiteList();

        // Configure Background Color for Coupon Button.
        $coupon_section->addStyleControls([
            [
                'name' => __('Coupon Button Background Color', 'wpfnl'),
                'selector' => $coupon_btn,
                'property' => 'background-color',
            ],
        ]);

        // Configure Hover Background Color for Coupon Button.
        $coupon_section->addStyleControls([
            [
                'name' => __('Coupon Button Hover Background Color', 'wpfnl'),
                'selector' => $coupon_btn . ':hover',
                'property' => 'background-color',
            ],
        ]);

        // Configure Hover Text Color for Coupon Button.
        $coupon_section->addStyleControls([
            [
                'name' => __('Coupon Button Hover Text Color', 'wpfnl'),
                'selector' => $coupon_btn . ':hover',
                'property' => 'color',
            ],
        ]);

        // Configure Typography for Coupon Button.
        $coupon_section->typographySection(__('Coupon Button Typography', 'wpfnl'), $coupon_btn, $this);

        // Configure Border Section for Coupon Button
        $coupon_section->borderSection(__('Coupon Button Border', 'wpfnl'), $coupon_btn, $this);
    }

    /**
     * Configure the styling for the multistep section on the checkout page.
     *
     * @since 2.8.2
     */
    private function configure_multistep_style() {
        // Add Control Section for Multistep Section
        $multistep_section = $this->addControlSection('multistep_section', __('Multistep Section', 'wpfnl'), 'assets/icon.png', $this);
        $step_title = '.wpfnl-multistep .wpfnl-multistep-wizard li .step-title';

        // Configure Typography for Step Title
        $multistep_section->typographySection(__('Step Title Typography', 'wpfnl'), $step_title, $this);

        // Configure Step Line Colors
        $multistep_section->addStyleControls([
            [
                'name' => __('Step Normal Line Color', 'wpfnl'),
                'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard:before',
                'property' => 'background-color',
            ],
            [
                'name' => __('Step Active/Completed Line Color', 'wpfnl'),
                'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard > li.completed:before, .wpfnl-multistep .wpfnl-multistep-wizard > li.current:before',
                'property' => 'background-color',
            ],
        ]);

        // Configure Step Box Background Colors
        $multistep_section->addStyleControls([
            [
                'name' => __('Step Box Normal Background Color', 'wpfnl'),
                'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li .step-icon',
                'property' => 'background-color',
            ],
            [
                'name' => __('Step Box Active/Completed Background Color', 'wpfnl'),
                'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li.completed .step-icon, .wpfnl-multistep .wpfnl-multistep-wizard li.current .step-icon',
                'property' => 'background-color',
            ],
        ]);

        // Configure Step Box Icon Colors
        $multistep_section->addStyleControls([
            [
                'name' => __('Step Box Icon Normal Color', 'wpfnl'),
                'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li .step-icon svg path',
                'property' => 'fill',
            ],
            [
                'name' => __('Step Box Icon Active/Completed Color', 'wpfnl'),
                'selector' => '.wpfnl-multistep .wpfnl-multistep-wizard li.completed .step-icon svg path, .wpfnl-multistep .wpfnl-multistep-wizard li.current .step-icon svg path',
                'property' => 'fill',
            ],
        ]);

        // Configure Step Box Borders
        $multistep_section->borderSection(__('Step Box Border', 'wpfnl'), '.wpfnl-multistep .wpfnl-multistep-wizard li .step-icon', $this);
        $multistep_section->borderSection(__('Step Box Active/Completed Border', 'wpfnl'), '.wpfnl-multistep .wpfnl-multistep-wizard li.completed .step-icon, .wpfnl-multistep .wpfnl-multistep-wizard li.current .step-icon', $this);

        // Add Control Section for Multistep Navigation
        $multistep_section_navigation = $this->addControlSection('multistep_section_navigation', __('Multistep Navigation', 'wpfnl'), 'assets/icon.png', $this);
        $multistep_nav = '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]';

        // Configure Typography for Navigation Button
        $multistep_section_navigation->typographySection(__('Navigation Button Typography', 'wpfnl'), $multistep_nav, $this);

        // Configure Border Section for Navigation Button
        $multistep_section_navigation->borderSection(__('Navigation Button Border', 'wpfnl'), $multistep_nav, $this);

        // Configure Padding for Navigation Button
        $multistep_section_navigation->addPreset('padding', 'multistep_nav_padding', __('Navigation Button Padding', 'wpfnl'), $multistep_nav)->whiteList();

        // Configure Navigation Button Background Colors
        $multistep_section_navigation->addStyleControls([
            [
                'name' => __('Navigation Button Background Color', 'wpfnl'),
                'selector' => $multistep_nav,
                'property' => 'background-color',
            ],
            [
                'name' => __('Navigation Button Hover Background Color', 'wpfnl'),
                'selector' => '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]:not(:disabled):hover',
                'property' => 'background-color',
            ],
        ]);

        // Configure Navigation Button Text and Border Colors on Hover
        $multistep_section_navigation->addStyleControls([
            [
                'name' => __('Navigation Button Hover Color', 'wpfnl'),
                'selector' => '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]:not(:disabled):hover',
                'property' => 'color',
            ],
            [
                'name' => __('Navigation Button Hover Border Color', 'wpfnl'),
                'selector' => '.wpfnl-multistep .wpfnl-multistep-navigation button[type=button]:not(:disabled):hover',
                'property' => 'border-color',
            ],
        ]);
    }

    function defaultCSS() {}
}