<?php
/**
 * Setup Wizard controller
 *
 * @package WPFunnels\Rest\Controllers
 */
namespace WPFunnels\Rest\Controllers;

use WP_REST_Request;
use WP_REST_Server;

class SetupWizardController extends Wpfnl_REST_Controller {

    /**
     * Endpoint namespace.
     *
     * @var string
     */
    protected $namespace = 'wpfunnels/v1';

    /**
     * Route base.
     *
     * @var string
     */
    protected $rest_base = 'setup-wizard';

    /**
     * Register REST routes.
     */
    public function register_routes() {
        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/complete-step',
            array(
                array(
                    'methods'             => WP_REST_Server::CREATABLE,
                    'callback'            => array( $this, 'complete_step' ),
                    'permission_callback' => function () {
                        return current_user_can( 'manage_options' );
                    },
                    'args' => array(
                        'funnelId' => array(
                            'description'       => __( 'Funnel ID.', 'wpfnl' ),
                            'type'              => 'integer',
                            'required'          => false,
                            'sanitize_callback' => 'absint',
                        ),
                        'action' => array(
                            'description'       => __( 'Completion action.', 'wpfnl' ),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/products',
            array(
                array(
                    'methods'             => WP_REST_Server::READABLE,
                    'callback'            => array( $this, 'get_products' ),
                    'permission_callback' => function () {
                        return current_user_can( 'manage_options' );
                    },
                    'args'                => array(
                        'search' => array(
                            'description'       => __( 'Search term for narrowing down WooCommerce products.', 'wpfnl' ),
                            'type'              => 'string',
                            'required'          => false,
                            'sanitize_callback' => 'sanitize_text_field',
                        ),
                        'limit'  => array(
                            'description'       => __( 'Maximum number of products to return.', 'wpfnl' ),
                            'type'              => 'integer',
                            'required'          => false,
                            'sanitize_callback' => 'absint',
                            'default'           => 3,
                        ),
                    ),
                ),
            )
        );
    }

    /**
     * Handle setup wizard completion step.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return \WP_REST_Response
     */
    public function complete_step( WP_REST_Request $request ) {
        $funnel_id = isset( $request['funnelId'] ) ? absint( $request['funnelId'] ) : 0;
        $action    = isset( $request['action'] ) ? sanitize_text_field( $request['action'] ) : '';
        $goal      = isset( $request['goal'] ) ? sanitize_text_field( $request['goal'] ) : '';

        /**
         * Fires when setup wizard completes an action.
         *
         * @param int            $funnel_id Funnel ID.
         * @param string         $action    Completion action.
         * @param string         $goal      Completion goal.
         */
        do_action( 'wpfunnels_setup_wizard_complete', $funnel_id, $action, $goal );

        return rest_ensure_response( array(
            'success' => true,
        ) );
    }

    /**
     * Retrieve WooCommerce products for the setup wizard Product Sync step.
     *
     * @param WP_REST_Request $request The REST request object.
     *
     * @return \WP_REST_Response
     */
    public function get_products( WP_REST_Request $request ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return rest_ensure_response( array(
                'success'  => false,
                'products' => array(),
                'message'  => __( 'WooCommerce needs to be active to sync products.', 'wpfnl' ),
            ) );
        }

        $search        = $request->get_param( 'search' );
        $search        = $search ? sanitize_text_field( wp_unslash( $search ) ) : '';
        $default_limit = $search ? 20 : 3;
        $max_limit     = $search ? 50 : 3;

        $limit = $request->get_param( 'limit' );
        $limit = $limit ? absint( $limit ) : $default_limit;
        $limit = $limit > 0 ? min( $limit, $max_limit ) : $default_limit;

        $products = array();

        if ( $search ) {
            $wp_args = array(
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => $limit,
                's'              => $search,
                'orderby'        => 'relevance',
                'order'          => 'ASC',
                'fields'         => 'ids',
            );

            $query      = new \WP_Query( $wp_args );
            $product_ids = $query->posts;

            if ( ! empty( $product_ids ) ) {
                foreach ( $product_ids as $pid ) {
                    $product = wc_get_product( $pid );
                    if ( $product ) {
                        $products[] = $product;
                    }
                }
            }
        } else {
            $args = array(
                'status'    => array( 'publish' ),
                'limit'     => $limit,
                'order'     => 'DESC',
                'return'    => 'objects',
                'orderby'   => 'meta_value_num',
                'meta_key'  => 'total_sales',
                'meta_type' => 'NUMERIC',
            );

            $products = wc_get_products( $args );
        }
        $prepared_data = array();

        if ( ! empty( $products ) ) {
            foreach ( $products as $product ) {
                if ( ! $product ) {
                    continue;
                }

                $raw_description = $product->get_short_description();
                if ( empty( $raw_description ) ) {
                    $raw_description = $product->get_description();
                }

                $description = wp_strip_all_tags( $raw_description );
                $description = $description ? wp_trim_words( $description, 24, '...' ) : __( 'WooCommerce product', 'wpfnl' );

                $price_html       = $product->get_price_html();
                $price_text       = $this->format_product_price( $product );
                $image_id   = $product->get_image_id();
                $image_url  = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : ( function_exists( 'wc_placeholder_img_src' ) ? wc_placeholder_img_src() : '' );

                $prepared_data[] = array(
                    'id'           => $product->get_id(),
                    'title'        => $product->get_name(),
                    'description'  => $description,
                    'price'        => $price_text,
                    'price_html'   => $price_html,
                    'type'         => $product->get_type(),
                    'stock_status' => $product->get_stock_status(),
                    'image'        => $image_url,
                    'permalink'    => get_permalink( $product->get_id() ),
                );
            }
        }

        return rest_ensure_response( array(
            'success'  => true,
            'products' => $prepared_data,
            'total'    => count( $prepared_data ),
            'message'  => empty( $prepared_data ) ? __( 'No products matched your query.', 'wpfnl' ) : '',
        ) );
    }

    /**
     * Convert WooCommerce price HTML into a readable one-line string.
     *
     * @param string $price_markup Raw WooCommerce price markup.
     * @return string
     */
    private function get_plain_price( $price_markup ) {
        if ( empty( $price_markup ) ) {
            return '';
        }

        // Remove screen reader helper text to avoid verbose sentences after strip tags.
        $price_markup = preg_replace( '/<span class="screen-reader-text">.*?<\/span>/is', '', $price_markup );

        $price_markup = html_entity_decode( wp_strip_all_tags( $price_markup ) );
        // Collapse duplicate whitespace.
        $price_markup = trim( preg_replace( '/\s+/', ' ', $price_markup ) );

        return $price_markup;
    }

    /**
     * Build a concise, human-readable price string with sale context when available.
     *
     * @param \WC_Product $product WooCommerce product instance.
     * @return string
     */
    private function format_product_price( $product ) {
        if ( ! $product ) {
            return '';
        }

        $regular_price = $product->get_regular_price();
        $sale_price    = $product->get_sale_price();

        if ( $product->is_type( array( 'variable', 'variable-subscription' ) ) ) {
            $regular_price = $product->get_variation_regular_price( 'min', true );
            $sale_price    = $product->get_variation_sale_price( 'min', true );
        }

        $regular_markup = $regular_price !== '' ? wc_price( $regular_price ) : wc_price( $product->get_price() );
        $regular_text   = $this->get_plain_price( $regular_markup );

        if ( $sale_price !== '' && $sale_price !== null && (float) $sale_price > 0 && (float) $sale_price < (float) $regular_price ) {
            $sale_markup = wc_price( $sale_price );
            $sale_text   = $this->get_plain_price( $sale_markup );
            return sprintf( '%s (%s %s)', $sale_text, __( 'was', 'wpfnl' ), $regular_text );
        }

        return $regular_text;
    }
}
