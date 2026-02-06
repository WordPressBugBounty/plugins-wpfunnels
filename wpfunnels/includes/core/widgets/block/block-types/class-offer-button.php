<?php
namespace WPFunnels\Widgets\Gutenberg\BlockTypes;

use WPFunnels\Wpfnl_functions;

/**
 * OfferButton class.
 */
class OfferButton extends AbstractBlock {

    protected $defaults = array(
        'offerAction' => 'accept',
        'buttonText' => 'Yes, Add to My Order!',
        'showProductPrice' => 'no',
        'buttonAlign' => 'left',
    );

    /**
     * Block name.
     *
     * @var string
     */
    protected $block_name = 'offer-button';


    public function __construct( $block_name = '' ) {
        parent::__construct($block_name);
        add_action('wp_ajax_wpfnl_offer_variable_shortcode', [$this, 'render_offer_variable_shortcode']);
        add_action( 'wp_ajax_nopriv_wpfnl_offer_variable_shortcode', [$this, 'render_offer_variable_shortcode'] ); 
    }

    /**
     * Extra data passed through from server to client for block.
     */
    protected function enqueue_data( array $attributes = [] ) {
        parent::enqueue_data( $attributes );
        
        $step_id = get_the_ID();
        $response = Wpfnl_functions::get_product_data_for_widget( $step_id );
        $is_variable = isset($response['get_product_type']) && in_array($response['get_product_type'], ['variable', 'variable-subscription']);
        $product_info = $this->get_dynamic_product_info_gutenberg( $step_id );
        
        // Generate variable product table HTML for editor
        $variable_render = '';
        if ( $is_variable && isset($response['product_variations']) ) {
            ob_start();
            echo '<table class="wpfnl-product-variation-table">';
            echo '<tbody>';
            foreach ( $response['product_variations'] as $variation ) {
                echo '<tr>';
                echo '<td>' . esc_html( $variation['name'] ) . '</td>';
                echo '<td>';
                if ( isset($variation['options']) && is_array($variation['options']) ) {
                    echo '<select>';
                    foreach ( $variation['options'] as $option ) {
                        echo '<option value="' . esc_attr( $option ) . '">' . esc_html( $option ) . '</option>';
                    }
                    echo '</select>';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody>';
            echo '</table>';
            $variable_render = ob_get_clean();
        }
        
        // Add offer-button specific data to wpfnl_pro_block_object
        wp_localize_script(
            'wpfnl-offer-button',
            'wpfnl_pro_block_object',
            array(
                'plugin'              => WPFNL_DIR_URL,
                'ajaxUrl'             => admin_url('admin-ajax.php'),
                'data_post_id'        => $step_id,
                'nonce'               => wp_create_nonce('wp_rest'),
                'isGbf'               => Wpfnl_functions::maybe_global_funnel( $step_id ),
                'is_variable_product' => $is_variable,
                'variableRender'      => $variable_render,
                'productInfo'         => $product_info
            )
        );
    }


    /**
     * Render the Offer Button block.
     *
     * @param array  $attributes Block attributes.
     * @param string $content    Block content.
     * @return string Rendered block type output.
     */
    protected function render( $attributes, $content ) {
        $attributes = wp_parse_args( $attributes, $this->defaults );
        $dynamic_css = $this->generate_assets($attributes);

        $response = Wpfnl_functions::get_product_data_for_widget( get_the_ID() );
        $is_variable = isset($response['get_product_type']) && in_array($response['get_product_type'], ['variable', 'variable-subscription']);
        $product_info = $this->get_dynamic_product_info_gutenberg( get_the_id());

        ob_start();
        ?>
        <div class="wp-block-wpfnl-offer-btn-<?php echo isset($attributes['buttonAlign']) ? $attributes['buttonAlign'] : ''; ?>" >
            <div class="wpfnl-offerbtn-wrapper" id="wpfnl-offerbtn-wrapper" >
                <?php
                    if( !isset($attributes['offerAction']) || 'reject' !== $attributes['offerAction'] ){
                        if( $is_variable ){
                            if( isset($attributes['variationTblTitle']) && !empty( $attributes['variationTblTitle'] ) ){ 
                                echo '<h5 class="wpfnl-product-variation-title">'.$attributes['variationTblTitle'].'</h5>';                       
                            }
                            ?>

                            <div class="has-variation-product">
                                <div class="wpfnl-product-variation">
                                    <?php 
                                        if( isset($attributes['showProductPrice']) && 'yes' === $attributes['showProductPrice'] ){
                                            echo '<span class="offer-btn-loader"></span>';
                                        }
                                        echo do_shortcode('[wpf_variable_offer]');
                                    ?>
                                </div>
                                <?php
                        }
                        
                    }
                ?>

                <div class="wpfnl-offerbtn-and-price-wrapper <?php echo isset($attributes['showProductPrice']) && 'yes' === $attributes['showProductPrice'] ? $attributes['productPriceAlignment'] : '' ?>">
                    <?php if( isset($attributes['showProductPrice']) && 'yes' === $attributes['showProductPrice'] ){ ?>
                        <span class="wpfnl-offer-product-price" id="wpfnl-offer-product-price">
                            <?php
                                if( !$is_variable && (!isset($attributes['offerAction']) || 'reject' !== $attributes['offerAction'] ) ){
                                    if( !empty($product_info['id']) ){
                                        $offer_product = wc_get_product($product_info['id']);
                                        if( $offer_product ){
                                            $step_type  = get_post_meta( get_the_ID(), '_step_type', true );
                                            $discount   = get_post_meta( get_the_ID(), '_wpfnl_'.$step_type.'_discount', true );
                                            $total_price = isset($response['quantity']) ? $offer_product->get_regular_price() * $response['quantity'] : $offer_product->get_regular_price();
                                            if( isset($discount['discountApplyTo'], $discount['discountType']) && 'original' !== $discount['discountType'] ){
                                                if( 'sale' === $discount['discountApplyTo'] ){
                                                    $sale_price = $offer_product->get_sale_price() ? $offer_product->get_sale_price() : $offer_product->get_regular_price();
                                                }elseif( 'regular' === $discount['discountApplyTo'] ){
                                                    $sale_price = $offer_product->get_regular_price() ? $offer_product->get_regular_price() : $offer_product->get_price();
                                                }else{
                                                    $sale_price = $offer_product->get_price();
                                                }
                                                $product_price = Wpfnl_functions::calculate_discount_price( $discount['discountType'] , $discount['discountValue'], $sale_price );
                                                if( $product_price != $total_price ){
                                                    echo wc_price(number_format( (float) $product_price, 2, '.', '' )).'<del>'.wc_price(number_format( (float) $total_price, 2, '.', '' )).'</del>';
                                                }else{
                                                    echo wc_price(number_format( (float) $product_price, 2, '.', '' ));
                                                }
                                            }else{
                                                if( $offer_product->get_sale_price() ){
                                                    $sale_price = $offer_product->get_sale_price();
                                                    $sale_price = isset($response['quantity']) ? $sale_price * $response['quantity'] : $sale_price;
                                                    echo wc_price(number_format( (float) $sale_price, 2, '.', '' )).'<del>'.wc_price(number_format( (float) $total_price, 2, '.', '' )).'</del>';
                                                }else{
                                                    echo wc_price(number_format( (float) $total_price, 2, '.', '' ));
                                                }    
                                            }
                                        }
                                    }
                                    
                                }
                            ?>
                        </span>
                        <?php
                    }

                    echo $content;
                    ?>
                </div>

                <?php
                    if( $is_variable && (!isset($attributes['offerAction']) || 'reject' !== $attributes['offerAction'] ) ) {
                        echo '</div>';
                        //end ".has-variation-product"
                    }
                ?>

            </div>
        </div>
        <?php

        if ( !did_action( 'wpfunnels/after_offer_button' ) ) {
            if( !isset($attributes['offerAction']) || (isset($attributes['offerAction']) && 'reject' !== $attributes['offerAction']) && Wpfnl_functions::is_wc_active() ){
                /**
                 * Fires after the offer button is displayed.
                 *
                 * This action hook allows developers to add custom functionality after the offer button is rendered on the page.
                 *
                 * @since 2.0.5
                 *
                 * @param void
                 */
                do_action( 'wpfunnels/after_offer_button' );
            }
        }

        return ob_get_clean();
    }


    /**
     * Get the frontend script handle for this block type.
     *
     * @see $this->register_block_type()
     * @param string $key Data to get, or default to everything.
     * @return array|string
     */
    protected function get_block_type_script( $key = null ) {
        $script = [
            'handle'       => 'wpfnl-offer-button-frontend',
            'path'         => $this->get_block_asset_build_path( 'offer-button-frontend' ),
            'dependencies' => [],
        ];
        return $key ? $script[ $key ] : $script;
    }

    /**
     * Render offer product shortcode markup
     * based on type
     */
    public function render_offer_variable_shortcode() {
        check_ajax_referer( 'wpfnl_gb_ajax_nonce', 'nonce' );
        $data = '';
        ob_start();
        echo do_shortcode('[wpf_variable_offer post_id="'.$_POST['id'].'" ]');
        $data = ob_get_clean();
        wp_send_json_success( $data );
    }

    /**
     * Get dynamic product info for Gutenberg.
     * 
     * @param String
     * @return String
     * 
     * @since 3.0.0
     */
    public function get_dynamic_product_info_gutenberg( $step_id ){
        if( $step_id ){
            $response = Wpfnl_functions::get_product_data_for_widget( $step_id );
            $offer_product = isset($response['offer_product']) && $response['offer_product'] ? $response['offer_product'] : '';

            if( $offer_product ){
                $product = [
                    'img' => get_the_post_thumbnail_url($offer_product->get_id()),
                    'title' => $offer_product->get_name(),
                    'description' => $offer_product->get_short_description(),
                    'price' => $offer_product->get_sale_price() ? $offer_product->get_sale_price() : $offer_product->get_regular_price(),
                    'id' => $offer_product->get_id(),
                ];

                return $product;
            }
        }
        
        return [
            'img' => '',
            'title' => 'What is Lorem Ipsum?',
            'description' => "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book.",
            'price' => "120",
        ];
    }
}
