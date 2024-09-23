<?php
/**
 * Step metas
 *
 * @package
 */
namespace WPFunnels\Metas;

class Wpfnl_Step_Meta_keys
{

    /**
     * List of all meta keys used
     * in different step initializations
     *
     * @param string $type
     *
     * @return mixed
     */
    public static function get_meta_keys($type = 'landing')
    {
        $meta_keys = [
            'landing' => [],
            'checkout' => [
                '_wpfnl_checkout_products' => [],
                '_wpfnl_checkout_discount' => [],
                '_wpfnl_checkout_coupon' => '',
            ],
            'thankyou' => [
                '_wpfnl_thankyou_text' => '',
                '_wpfnl_thankyou_redirect_link' => '',
                '_wpfnl_thankyou_order_overview' => 'on',
                '_wpfnl_thankyou_order_details' => 'on',
                '_wpfnl_thankyou_billing_details' => 'on',
                '_wpfnl_thankyou_shipping_details' => 'on',
            ],
            'upsell' => [
                '_wpfnl_upsell_product' => [],
                '_wpfnl_upsell_discount_type' => '',
                '_wpfnl_upsell_discount_value' => '',
                '_wpfnl_upsell_product_price' => '',
                '_wpfnl_upsell_product_sale_price' => '',
                '_wpfnl_upsell_hide_image' => 'off',
                '_wpfnl_upsell_next_step_yes' => '',
                '_wpfnl_upsell_next_step_no' => '',
            ],
            'downsell' => [
                '_wpfnl_downsell_product' => [],
                '_wpfnl_downsell_discount_type' => '',
                '_wpfnl_downsell_discount_value' => '',
                '_wpfnl_downsell_product_price' => '',
                '_wpfnl_downsell_product_sale_price' => '',
                '_wpfnl_downsell_hide_image' => 'off',
                '_wpfnl_downsell_next_step_yes' => '',
                '_wpfnl_downsell_next_step_no' => '',
            ],
            'custom'        => [],
            'trigger'    => [],
        ];
        $meta_keys = apply_filters( 'wpfunnels/supported_steps_key', $meta_keys );

        return $meta_keys[$type];
    }


    /**
     * Get meta keys and its sanitization fields for landing step
     *
     * @return array[]
     * @since 3.1.0
     *
     * @todo : Need to add all landing meta data within this function
     */
    public static function get_landing_meta() {
        return array(
            '_wpfnl_custom_script'   => array(
                'default'  => '',
                'sanitize_func' => 'FILTER_SCRIPT',
            ),
        );
    }

    /**
     * Get meta keys and its sanitization fields for checkout step
     *
     * @return array[]
     * @since 3.1.0
     *
     * @todo : Need to add all checkout meta data within this function
     */
    public static function get_checkout_meta() {
        return array(
            '_wpfnl_custom_script'   => array(
                'default'  => '',
                'sanitize_func' => 'FILTER_SCRIPT',
            ),
        );
    }

    /**
     * Get meta keys and its sanitization fields for thankyou step
     *
     * @return array[]
     * @since 3.1.0
     *
     * @todo : Need to add all thankyou meta data within this function
     */
    public static function get_thankyou_meta() {
        return array(
            '_wpfnl_custom_script'   => array(
                'default'  => '',
                'sanitize_func' => 'FILTER_SCRIPT',
            ),
        );
    }

    /**
     * Get meta keys and its sanitization fields for offer step
     *
     * @return array[]
     * @since 3.1.0
     *
     * @todo : Need to add all offer meta data within this function
     */
    public static function get_offer_meta() {
        return array(
            '_wpfnl_custom_script'   => array(
                'default'  => '',
                'sanitize_func' => 'FILTER_SCRIPT',
            ),
        );
    }

    /**
     * Get meta keys and its sanitization fields for custom step
     *
     * @return array[]
     * @since 3.1.0
     *
     * @todo : Need to add all custom meta data within this function
     */
    public static function get_custom_meta() {
        return array(
            '_wpfnl_custom_script'   => array(
                'default'  => '',
                'sanitize_func' => 'FILTER_SCRIPT',
            ),
        );
    }


    /**
     * Save meta field of steps
     *
     * @param $step_id
     * @param $settings
     * @param $default_meta
     * @since 3.1.0
     */
    public static function save_meta( $step_id, $settings, $default_meta ) {
        foreach ( $default_meta as $key => $data ) {
            if ( ! isset( $settings[ $key ] ) ) {
                continue;
            }
            $sanitize_filter = $data['sanitize_func'];
            $meta_value      = '';
            switch ( $sanitize_filter ) {
                case 'FILTER_SCRIPT':
                    $meta_value = htmlentities( wp_unslash( $settings[ $key ] ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
                    break;
                default:
                    break;
            }
            update_post_meta($step_id, $key, $meta_value );
        }
    }
}
