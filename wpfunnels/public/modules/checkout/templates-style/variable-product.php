<?php
/**
 * Orderbump variable product template
 *
 * @package
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $product;

$attribute_keys  = array_keys( $attributes );
$variations_json = wp_json_encode( $available_variations );
$variations_attr = function_exists( 'wc_esc_json' ) ? wc_esc_json( $variations_json ) : _wp_specialchars( $variations_json, ENT_QUOTES, 'UTF-8', true );
?>

<div class="wpfnl-variable-product-modal-content product">
	<div class="wp-block-columns alignwide is-layout-flex wp-container-core-columns-is-layout-28f84493 wp-block-columns-is-layout-flex">
		<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">
			<div class="woocommerce-product-gallery woocommerce-product-gallery--with-images woocommerce-product-gallery--columns-4 images" data-columns="4" style="width: 100%">
				<figure class="woocommerce-product-gallery__wrapper">
					<?php
					$image_id = $product->get_image_id();
					$image_url = wp_get_attachment_image_url( $image_id, 'full' );
					echo '<div class="woocommerce-product-gallery__image"><a href="' . esc_url( $image_url ) . '"><img src="' . esc_url( $image_url ) . '" class="wp-post-image" alt="' . esc_attr( get_post_meta( $image_id, '_wp_attachment_image_alt', true ) ) . '"></a></div>';
					?>
				</figure>
				<ol class="flex-control-nav flex-control-thumbs">
					<?php
					$attachment_ids = $product->get_gallery_image_ids();
					if ( $attachment_ids && $product->get_image_id() ) {
						$image_class = 'flex-active';
						$image_url = wp_get_attachment_image_url( $product->get_image_id(), 'thumbnail' );
						$full_src_url = wp_get_attachment_image_url( $product->get_image_id(), 'full' );
						echo '<li><img src="' . esc_url( $image_url ) . '" class="' . esc_attr( $image_class ) . '" data-full_src="' . esc_url( $full_src_url ) . '"></li>';
						foreach ( $attachment_ids as $attachment_id ) {
							$image_url = wp_get_attachment_image_url( $attachment_id, 'thumbnail' );
							$full_src_url = wp_get_attachment_image_url( $attachment_id, 'full' );
							echo '<li><img src="' . esc_url( $image_url ) . '" data-full_src="' . esc_url( $full_src_url ) . '"></li>';
						}
					}
					?>
				</ol>
			</div>
		</div>
		<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow">
			<h1 class="product_title entry-title"><?php echo esc_html( $product->get_name() ); ?></h1>
			<?php woocommerce_template_single_excerpt(); ?>
			<form class="variations_form cart" action="" method="post" enctype='multipart/form-data' data-product_id="<?php echo absint( $product->get_id() ); ?>" data-product_variations="<?php echo $variations_attr; // WPCS: XSS ok. ?>">
				<?php if ( empty( $available_variations ) && false !== $available_variations ) : ?>
				<p class="stock out-of-stock"><?php echo esc_html( apply_filters( 'woocommerce_out_of_stock_message', __( 'This product is currently out of stock and unavailable.', 'wpfnl' ) ) ); ?></p>
			<?php else : ?>
				<table class="variations" cellspacing="0">
				<tbody>
					<?php foreach ( $attributes as $attribute_name => $options ) : ?>
						<tr>
							<td class="label"><label for="<?php echo esc_attr( sanitize_title( $attribute_name ) ); ?>"><?php echo wc_attribute_label( $attribute_name ); // WPCS: XSS ok. ?></label></td>
							<td class="value">
								<?php
									wc_dropdown_variation_attribute_options(
										array(
											'options'   => $options,
											'attribute' => $attribute_name,
											'product'   => $product,
										)
									);
									echo end( $attribute_keys ) === $attribute_name ? wp_kses_post( apply_filters( 'woocommerce_reset_variations_link', '<a class="reset_variations" href="#">' . esc_html__( 'Clear', 'wpfnl' ) . '</a>' ) ) : '';
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<div class="description">
				<p class="description-label">Description: </p>
				<?php echo apply_filters( 'woocommerce_short_description', $product->get_short_description() ); ?>
			</div>

			<p class="price"><?php echo $product->get_price_html(); ?></p>
			<div class="single_variation_wrap">
				<?php
					/**
					 * Hook: woocommerce_before_single_variation.
					 */
					do_action( 'woocommerce_before_single_variation' );

					/**
					 * Hook: woocommerce_single_variation. Used to output the cart button and placeholder for variation data.
					 *
					 * @since 2.4.0
					 * @hooked woocommerce_single_variation - 10 Empty div for variation data.
					 * @hooked woocommerce_single_variation_add_to_cart_button - 20 Qty and cart button.
					 */
					do_action( 'woocommerce_single_variation' );

					/**
					 * Hook: woocommerce_after_single_variation.
					 */
					do_action( 'woocommerce_after_single_variation' );
				?>
			</div>
		<?php endif; ?>
	</form>
	</div>
</div>
</div>
