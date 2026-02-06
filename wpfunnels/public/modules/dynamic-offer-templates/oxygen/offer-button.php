<div class="oxy-offer-button-<?php echo isset($options['button_alignment']) ? $options['button_alignment'] : ''; ?>">
    <div class="wpfnl-offerbtn-wrapper" id="wpfnl-offerbtn-wrapper" >
        <?php 
            
            if( ('variable' === $get_product_type || 'variable-subscription' === $get_product_type) &&  'accept' === $button_action ) {

                if( !empty( $variation_tbl_title ) ){
                    echo '<h5 class="wpfnl-product-variation-title">'.$variation_tbl_title.'</h5>';
                }
                
                echo '<div class="has-variation-product">';
                    echo '<div class="wpfnl-product-variation">';
                        if( 'yes' === $show_product_price ){
                            echo '<span class="offer-btn-loader"></span>';
                        }
                        echo  do_shortcode( '[wpf_variable_offer post_id="'.$step_id.'"]' );
                    echo '</div>';
                    
            }
        ?>

        <div class="wpfnl-offerbtn-and-price-wrapper <?php echo 'yes' === $options['show_product_price'] ? $options['product_price_alignment'] : '' ?>">
            <?php if( 'yes' === $show_product_price ){ ?>
                <span class="wpfnl-offer-product-price" id="wpfnl-offer-product-price">
                    <?php
                        if( ('variable' !== $get_product_type && 'variable-subscription' !== $get_product_type) &&  'accept' === $button_action ){
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
                                    $product_price 		= \WPFunnelsPro\Wpfnl_Pro_functions::calculate_discount_price_for_widget( $discount['discountType'] , $discount['discountValue'], $sale_price  );
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
                    ?>
                </span>
            <?php } ?>

            <a class="btn-default wpfnl-oxy-offer-btn offer-button wpfunnels_offer_button" data-offertype="<?php echo $button_type ?>" id="<?php echo $button_id ?>"> <?php echo $options['title_text'] ?> </a>
        </div>

        <?php 
            if( ('variable' === $get_product_type || 'variable-subscription' === $get_product_type) &&  'accept' === $button_action ) {
                echo '</div>';
                //end ".has-variation-product"
            }
        ?>

    </div>
</div>


<?php
    if ( did_action( 'wpfunnels/after_offer_button' ) ) {
        return;
    }
    if( 'accept' === $button_action  && \WPFunnels\Wpfnl_functions::maybe_unsupported_payment_gateway() ){
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
?>