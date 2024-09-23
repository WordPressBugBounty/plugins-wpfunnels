<?php
/**
 * Variable product add to cart
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/single-product/add-to-cart/variable.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see     https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 6.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( !$product ) {
    return;
}

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
?>
<?php

    if( \WPFunnelsPro\Wpfnl_Pro_functions::maybe_admin_on_edit_page() ) { ?>
        <div class="variations_form cart">
    <?php } else { ?>
        <div class="variations_form cart" action="<?php echo esc_url( apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() ) ); ?>" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
    <?php } ?>

	<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
		<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'woocommerce' ) ) ); ?></p>
	<?php else : ?>
		
		<div class="offer-variation-wrapper variations">
			<?php foreach ( $attributes as $attribute_name => $options ) : ?>
				<div class="offer-product-single-variation <?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>" >
					<div class="variation-attr-name label" >
						<?php echo wc_attribute_label( $attribute_name ); // WPCS: XSS ok. ?>
					</div>

					<div class="variation-attr-value value">
						<?php
							wc_dropdown_variation_attribute_options(
								array(
									'options'   		=> $options,
									'attribute' 		=> $attribute_name,
									'product'   		=> $product,
									'class'     		=> 'wpfnl-variable-attribute-offer wpfnl-variation',
								)
							);
							
						?>
					</div>

				</div>

				
			<?php 
			echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear Variations', 'woocommerce' ) . '</a>' ) ) : '';
				endforeach; 
			?>
			<input type="hidden" class="wpfnl-varition-qty" value="<?php echo $quantity ?>" >
			<input type="hidden" class="wpfnl-product-id" value="<?php echo $product->get_id() ?>" >
			<span class="wpfnl-select-variation"></span>
		</div>

	<?php endif; ?>
</div>
